<?php

namespace App\Repositories\Eloquent;

use App\Models\Invoice;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\InvoiceInterface;

class InvoiceRepository extends BaseRepository implements InvoiceInterface
{
    protected array $allowedFilters = [
        'search' => [
            'type' => 'like',
            'columns' => ['invoice_number', 'notes'],
        ],
        'member_id' => [
            'type' => 'exact',
            'column' => 'member_id',
        ],
        'status' => [
            'type' => 'in',
            'column' => 'status',
        ],
        'invoice_type' => [
            'type' => 'exact',
            'column' => 'invoice_type',
        ],
        'min_total' => [
            'type' => 'min',
            'column' => 'total_amount',
        ],
        'max_total' => [
            'type' => 'max',
            'column' => 'total_amount',
        ],
        'from_date' => [
            'type' => 'date_min',
            'column' => 'created_at',
        ],
        'to_date' => [
            'type' => 'date_max',
            'column' => 'created_at',
        ],
    ];

    protected array $allowedSorts = [
        'id',
        'invoice_number',
        'subtotal',
        'tax',
        'discount',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'due_date',
        'created_at',
        'updated_at',
    ];

    public function all()
    {
        return Invoice::with(['member', 'payments'])->latest()->get();
    }

    public function getAll(array $filters = [])
    {
        $query = $this->query();

        if (
            !isset($filters['sort_by'])
            || !in_array($filters['sort_by'], $this->allowedSorts, true)
        ) {
            $query->latest('id');
        }

        return $this->getPaginated($query, $filters);
    }

    public function find(int $id): ?Invoice
    {
        return Invoice::with(['member', 'payments', 'fines'])->find($id);
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
        return Invoice::with(['member', 'payments', 'fines'])->where('invoice_number', $invoiceNumber)->first();
    }

    public function fineInvoiceByMember(int $memberId): ?Invoice
    {
        return Invoice::with(['member', 'payments', 'fines'])
            ->where('member_id', $memberId)
            ->where('invoice_type', 'fine')
            ->where('status', '!=', 'paid')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Base query with the relations an invoice listing needs.
     */
    protected function query()
    {
        return Invoice::query()
            ->with(['member', 'payments', 'fines']);
    }
}