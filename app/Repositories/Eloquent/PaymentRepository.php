<?php

namespace App\Repositories\Eloquent;

use App\Models\Payment;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\PaymentRepositoryInterface;

class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    public function getAll(array $filters = [])
    {
        $query = Payment::with(['booking']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        if (!empty($filters['booking_id'])) {
            $query->where('booking_id', $filters['booking_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        if (!empty($filters['user_id'])) {
            $query->whereHas('booking', function ($q) use ($filters) {
                $q->where('booked_by_user_id', $filters['user_id']);
            });
        }

        $query->latest();

        $paginator = $this->applyPagination($query, $filters);

        return [
            'data' => $paginator->items(),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    public function findById(int $id)
    {
        return Payment::with(['booking'])->find($id);
    }

    public function create(array $data)
    {
        return Payment::create($data);
    }

    public function existsByReference(string $reference): bool
    {
        return Payment::where('transaction_id', $reference)->exists();
    }
}
