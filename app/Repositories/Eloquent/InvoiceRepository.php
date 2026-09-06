<?php

namespace App\Repositories\Eloquent;

use App\Models\Invoice;
use App\Repositories\Interface\InvoiceInterface;

class InvoiceRepository implements InvoiceInterface
{
    public function all()
    {
        return Invoice::with(['member', 'payments'])->latest()->get();
    }

    public function find(int $id): ?Invoice
    {
        return Invoice::with(['member', 'payments'])->find($id);
    }

    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    public function update(int $id, array $data): Invoice
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->update($data);

        return $invoice->fresh();
    }

    public function delete(int $id): bool
    {
        return Invoice::findOrFail($id)->delete();
    }

    public function findByMember(int $memberId): ?Invoice
    {
        return Invoice::where('member_id', $memberId)
            ->whereIn('status', ['unpaid', 'cancelled', 'refunded'])
            ->orderByDesc('id')
            ->first();
    }

    public function findByNumber(string $invoiceNumber): ?Invoice
    {
        return Invoice::with(['member', 'payments'])->where('invoice_number', $invoiceNumber)->first();
    }
}
