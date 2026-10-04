<?php

namespace App\Repositories\Eloquent;

use App\Models\CentralInvoice;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\CentralInvoiceInterface;

class CentralInvoiceRepository extends BaseRepository implements CentralInvoiceInterface
{
    protected array $allowedFilters = [
        'search' => [
            'type' => 'like',
            'columns' => ['invoice_number', 'notes'],
        ],
        'tenant_id' => [
            'type' => 'exact',
            'column' => 'tenant_id',
        ],
        'subscription_id' => [
            'type' => 'exact',
            'column' => 'subscription_id',
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
        return $this->query()->latest('id')->get();
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

    public function find(int $id): ?CentralInvoice
    {
        return $this->query()->find($id);
    }

    public function findByNumber(string $invoiceNumber): ?CentralInvoice
    {
        return $this->query()
            ->where('invoice_number', $invoiceNumber)
            ->first();
    }

    public function create(array $data): CentralInvoice
    {
        return $this->query()->create($data);
    }

    public function update(int $id, array $data): CentralInvoice
    {
        $centralInvoice = CentralInvoice::withoutGlobalScopes()
            ->findOrFail($id);

        $centralInvoice->update($data);

        return $centralInvoice->fresh();
    }

    public function delete(int $id): bool
    {
        return CentralInvoice::withoutGlobalScopes()
            ->findOrFail($id)
            ->delete();
    }

    /**
     * Base query with the relations a central invoice listing needs.
     */
    protected function query()
    {
        return CentralInvoice::query()
            ->with(['tenant', 'subscription', 'subscriptionPayment']);
    }
}
