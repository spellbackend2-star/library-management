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
        $schema = Schema::connection($connection);

        if (! $schema->hasTable('subscription_invoices')) {
            return;
        }

        if (! $schema->hasTable('invoices')) {
            throw new RuntimeException(
                'Cannot consolidate subscription invoices because the central invoices table does not exist.'
            );
        }

        $this->validateExistingInvoices($connection);
        $this->migrateRemainingInvoices($connection);
        $this->validateAllInvoicesMigrated($connection);
        $this->dropForeignKey($connection, 'invoices');
        $this->repointSubscriptionPayments($connection);
        $this->validatePaymentLinks($connection);
        $this->moveForeignKey($connection);

        DB::connection($connection)->statement('DROP TABLE subscription_invoices');
    }

    public function down(): void
    {
        $connection = $this->centralConnection();

        if (Schema::connection($connection)->hasTable('subscription_invoices')) {
            return;
        }

        $schema = Schema::connection($connection);

        $schema->create('subscription_invoices', function (Blueprint $table) {
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

        $rows = DB::connection($connection)
            ->table('invoices')
            ->whereIn('invoice_type', ['subscription', 'renewal'])
            ->get();

        foreach ($rows->chunk(200) as $chunk) {
            $legacyRows = $chunk->map(fn ($row) => [
                'id' => $row->id,
                'tenant_id' => $row->tenant_id,
                'subscription_id' => $row->subscription_id,
                'invoice_number' => $row->invoice_number,
                'invoice_type' => $row->invoice_type,
                'subtotal' => $row->subtotal,
                'tax' => $row->tax,
                'discount' => $row->discount,
                'total_amount' => $row->total_amount,
                'paid_amount' => $row->paid_amount,
                'remaining_amount' => $row->remaining_amount,
                'status' => $row->status,
                'due_date' => $row->due_date,
                'notes' => $row->notes,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ])->all();

            DB::connection($connection)
                ->table('subscription_invoices')
                ->insert($legacyRows);
        }

        $unmappedPayments = DB::connection($connection)
            ->table('subscription_payments as payments')
            ->leftJoin('subscription_invoices as invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->whereNotNull('payments.invoice_id')
            ->whereNull('invoices.id')
            ->exists();

        if ($unmappedPayments) {
            throw new RuntimeException(
                'Cannot roll back invoice consolidation because some subscription payments do not have a matching subscription invoice.'
            );
        }

        $this->dropForeignKey($connection, 'invoices');
        $this->addForeignKey($connection, 'subscription_invoices');
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
            $legacyPaid = (float) ($row->paid_amount ?? 0);
            $successfulPaid = $this->successfulPaymentAmount($connection, (int) $row->id);
            $paid = max($legacyPaid, $successfulPaid);
            $remaining = (float) ($row->remaining_amount ?? max(0, $total - $legacyPaid));
            $status = $row->status ?? 'unpaid';

            if (
                $successfulPaid > $legacyPaid
                && ! in_array($status, ['cancelled', 'refunded'], true)
            ) {
                $paid = min($paid, $total);
                $remaining = max(0, round($total - $paid, 2));
                $status = match (true) {
                    $remaining <= 0 => 'paid',
                    $paid > 0 => 'partially_paid',
                    default => $status,
                };
            }

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
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
                'currency' => 'NPR',
                'currency_symbol' => 'Rs.',
                'status' => $status,
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
        DB::connection($connection)
            ->table('subscription_payments as payments')
            ->join('subscription_invoices as legacy', 'legacy.id', '=', 'payments.invoice_id')
            ->join('invoices as central', 'central.invoice_number', '=', 'legacy.invoice_number')
            ->update(['payments.invoice_id' => DB::raw('central.id')]);
    }

    protected function validateExistingInvoices(string $connection): void
    {
        $conflictingInvoice = DB::connection($connection)
            ->table('subscription_invoices as legacy')
            ->join('invoices as central', 'central.invoice_number', '=', 'legacy.invoice_number')
            ->where(function ($query) {
                $query->whereColumn('central.total_amount', '<>', 'legacy.total_amount')
                    ->orWhere(function ($query) {
                        $query->whereNotNull('central.subscription_id')
                            ->whereNotNull('legacy.subscription_id')
                            ->whereColumn('central.subscription_id', '<>', 'legacy.subscription_id');
                    })
                    ->orWhere(function ($query) {
                        $query->whereNotNull('central.tenant_id')
                            ->whereNotNull('legacy.tenant_id')
                            ->whereColumn('central.tenant_id', '<>', 'legacy.tenant_id');
                    });
            })
            ->exists();

        if ($conflictingInvoice) {
            throw new RuntimeException(
                'Cannot consolidate invoices because matching invoice numbers have conflicting totals or ownership. No legacy invoice data was deleted.'
            );
        }
    }

    protected function validateAllInvoicesMigrated(string $connection): void
    {
        $missingInvoice = DB::connection($connection)
            ->table('subscription_invoices as legacy')
            ->leftJoin('invoices as central', 'central.invoice_number', '=', 'legacy.invoice_number')
            ->whereNull('central.id')
            ->exists();

        if ($missingInvoice) {
            throw new RuntimeException(
                'Cannot consolidate invoices because at least one legacy invoice was not copied. No legacy invoice data was deleted.'
            );
        }
    }

    protected function validatePaymentLinks(string $connection): void
    {
        $unlinkedPayment = DB::connection($connection)
            ->table('subscription_payments as payments')
            ->leftJoin('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->whereNotNull('payments.invoice_id')
            ->whereNull('invoices.id')
            ->exists();

        if ($unlinkedPayment) {
            throw new RuntimeException(
                'Cannot consolidate invoices because at least one subscription payment is not linked to a central invoice. No legacy invoice data was deleted.'
            );
        }
    }

    protected function successfulPaymentAmount(string $connection, int $invoiceId): float
    {
        return (float) DB::connection($connection)
            ->table('subscription_payments')
            ->where('invoice_id', $invoiceId)
            ->whereIn('status', ['SUCCESS', 'COMPLETED'])
            ->sum('amount');
    }

    /**
     * Re-add the foreign key on subscription_payments.invoice_id.
     */
    protected function moveForeignKey(string $connection): void
    {
        $this->addForeignKey($connection, 'invoices');
    }

    /**
     * Drop the old foreign key that referenced subscription_invoices.
     */
    protected function dropForeignKey(string $connection, string $preserveTarget): void
    {
        $foreignKey = $this->invoiceForeignKey($connection);

        if (! $foreignKey || $foreignKey['foreign_table'] === $preserveTarget) {
            return;
        }

        if ($foreignKey['foreign_table'] !== 'subscription_invoices' && $foreignKey['foreign_table'] !== 'invoices') {
            throw new RuntimeException(
                'Cannot consolidate invoices because subscription_payments.invoice_id references an unexpected table.'
            );
        }

        Schema::connection($connection)->table('subscription_payments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });
    }

    protected function addForeignKey(string $connection, string $target): void
    {
        $foreignKey = $this->invoiceForeignKey($connection);

        if ($foreignKey && $foreignKey['foreign_table'] === $target) {
            return;
        }

        if ($foreignKey) {
            throw new RuntimeException(
                'Cannot update the subscription payment invoice foreign key because it references an unexpected table.'
            );
        }

        Schema::connection($connection)->table('subscription_payments', function (Blueprint $table) use ($target) {
            $table->foreign('invoice_id')
                ->references('id')
                ->on($target)
                ->nullOnDelete();
        });
    }

    protected function invoiceForeignKey(string $connection): ?array
    {
        foreach (Schema::connection($connection)->getForeignKeys('subscription_payments') as $foreignKey) {
            if ($foreignKey['columns'] === ['invoice_id']) {
                return $foreignKey;
            }
        }

        return null;
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