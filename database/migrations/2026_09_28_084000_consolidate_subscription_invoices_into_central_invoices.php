<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Centralise every subscription invoice into the central "invoices"
     * table and retire "subscription_invoices".
     *
     * Steps:
     *  1. Copy any remaining rows from "subscription_invoices" into
     *     "invoices" (matching on the preserved invoice number).
     *  2. Repoint subscription_payments.invoice_id to the new ids.
     *  3. Move the foreign key from "subscription_invoices" to "invoices".
     *  4. Drop "subscription_invoices".
     */
    public function up(): void
    {
        $connection = $this->centralConnection();

        if (! Schema::connection($connection)->hasTable('subscription_invoices')) {
            return;
        }

        $this->migrateRemainingInvoices($connection);
        $this->dropForeignKey($connection);
        $this->repointSubscriptionPayments($connection);
        $this->moveForeignKey($connection);

        DB::connection($connection)->statement('DROP TABLE subscription_invoices');
    }

    public function down(): void
    {
        $connection = $this->centralConnection();

        if (Schema::connection($connection)->hasTable('subscription_invoices')) {
            return;
        }

        Schema::connection($connection)->create('subscription_invoices', function (Blueprint $table) {
            $table->id();

            $table->string('tenant_id', 36)->nullable();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();

            $table->foreignId('subscription_id')
                ->nullable()
                ->constrained('subscriptions')
                ->cascadeOnDelete();

            $table->string('invoice_number')->unique();

            $table->enum('invoice_type', [
                'subscription',
                'renewal',
            ])->default('subscription');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);

            $table->enum('status', [
                'unpaid',
                'partially_paid',
                'paid',
                'overdue',
                'cancelled',
                'refunded',
            ])->default('unpaid');

            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('subscription_id');
            $table->index('status');
            $table->index('due_date');
        });

        $this->revertSubscriptionPayments($connection);

        Schema::connection($connection)->table('subscription_payments', function (Blueprint $table) {
            $table->foreign('invoice_id')
                ->references('id')
                ->on('subscription_invoices')
                ->nullOnDelete();
        });
    }

    /**
     * Copy rows that are not present in the central invoices table yet.
     */
    protected function migrateRemainingInvoices(string $connection): void
    {
        $existing = DB::connection($connection)
            ->table('invoices')
            ->pluck('invoice_number')
            ->all();

        $rows = DB::connection($connection)
            ->table('subscription_invoices')
            ->when(count($existing) > 0, fn ($q) => $q->whereNotIn('invoice_number', $existing))
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $now = now();

        $insert = $rows->map(function ($row) use ($connection, $now) {
            $total = (float) $row->total_amount;

            return [
                'tenant_id' => $row->tenant_id,
                'subscription_id' => $row->subscription_id,
                'subscription_payment_id' => $this->paymentIdFor($connection, $row->id),
                'invoice_number' => $row->invoice_number,
                'invoice_type' => $row->invoice_type ?? 'subscription',
                'subtotal' => $row->subtotal ?? $total,
                'tax' => $row->tax ?? 0,
                'discount' => $row->discount ?? 0,
                'total_amount' => $total,
                'paid_amount' => $row->paid_amount ?? 0,
                'remaining_amount' => $row->remaining_amount ?? 0,
                'currency' => 'NPR',
                'currency_symbol' => 'Rs.',
                'status' => $row->status ?? 'unpaid',
                'due_date' => $row->due_date,
                'notes' => $row->notes,
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $row->updated_at ?? $now,
            ];
        })->all();

        foreach (array_chunk($insert, 200) as $chunk) {
            DB::connection($connection)->table('invoices')->insert($chunk);
        }
    }

    /**
     * Point every subscription payment at the matching central invoice.
     */
    protected function repointSubscriptionPayments(string $connection): void
    {
        $map = DB::connection($connection)->table('subscription_invoices')
            ->join('invoices', 'invoices.invoice_number', '=', 'subscription_invoices.invoice_number')
            ->pluck('invoices.id', 'subscription_invoices.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($map === []) {
            return;
        }

        foreach ($map as $oldId => $newId) {
            DB::connection($connection)->table('subscription_payments')
                ->where('invoice_id', $oldId)
                ->update(['invoice_id' => $newId]);
        }
    }

    /**
     * Reset subscription payments that no longer resolve to an invoice.
     */
    protected function revertSubscriptionPayments(string $connection): void
    {
        $map = DB::connection($connection)->table('invoices')
            ->join('subscription_invoices', 'subscription_invoices.invoice_number', '=', 'invoices.invoice_number')
            ->pluck('subscription_invoices.id', 'invoices.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($map === []) {
            return;
        }

        foreach ($map as $newId => $oldId) {
            DB::connection($connection)->table('subscription_payments')
                ->where('invoice_id', $newId)
                ->update(['invoice_id' => $oldId]);
        }
    }

    /**
     * Re-add the foreign key on subscription_payments.invoice_id.
     */
    protected function moveForeignKey(string $connection): void
    {
        Schema::connection($connection)->table('subscription_payments', function (Blueprint $table) {
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->nullOnDelete();
        });
    }

    /**
     * Drop the old foreign key that referenced subscription_invoices.
     */
    protected function dropForeignKey(string $connection): void
    {
        Schema::connection($connection)->table('subscription_payments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });
    }

    /**
     * Resolve the payment that points at a given subscription invoice.
     */
    protected function paymentIdFor(string $connection, int $invoiceId): ?int
    {
        return DB::connection($connection)
            ->table('subscription_payments')
            ->where('invoice_id', $invoiceId)
            ->orderByDesc('id')
            ->value('id');
    }

    /**
     * Central database connection name.
     */
    protected function centralConnection(): string
    {
        return config('tenancy.database.central_connection')
            ?? config('database.default');
    }
};