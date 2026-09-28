<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Copy every existing central invoice from "subscription_invoices"
     * into the new central "invoices" table so the platform has no
     * historical gap. The original invoice number is preserved to
     * keep existing references valid.
     */
    public function up(): void
    {
        $connection = $this->centralConnection();

        if (! Schema::connection($connection)->hasTable('subscription_invoices')) {
            return;
        }

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

    public function down(): void
    {
        $connection = $this->centralConnection();

        $numbers = DB::connection($connection)
            ->table('subscription_invoices')
            ->pluck('invoice_number')
            ->all();

        DB::connection($connection)
            ->table('invoices')
            ->whereIn('invoice_number', $numbers)
            ->delete();
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
