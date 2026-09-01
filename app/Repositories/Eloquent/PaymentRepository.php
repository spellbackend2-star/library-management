<?php

namespace App\Repositories\Eloquent;

use App\Models\Payment;
use App\Repositories\Interface\PaymentRepositoryInterface;

class PaymentRepository implements PaymentRepositoryInterface
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

        return $query->latest()->get();
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
