<?php

namespace App\Repositories\Eloquent;

use App\Models\BookingSeat;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\BookingSeatInterface;

class BookingSeatRepository extends BaseRepository implements BookingSeatInterface
{
    protected array $allowedSorts = [
        'id',
        'booking_id',
        'seat_id',
        'start_at',
        'end_at',
        'status',
        'created_at',
    ];

    protected array $allowedFilters = [
        'booking_id' => [
            'type' => 'exact',
            'column' => 'booking_id',
        ],
        'seat_id' => [
            'type' => 'exact',
            'column' => 'seat_id',
        ],
        'status' => [
            'type' => 'exact',
            'column' => 'status',
        ],
    ];

    public function all()
    {
        return BookingSeat::with(['booking', 'seat'])->latest()->get();
    }

    public function getAll(array $filters = []): array
    {
        $query = BookingSeat::with(['booking', 'seat']);

        return $this->getPaginated($query, $filters);
    }

    public function find(int $id): ?BookingSeat
    {
        return BookingSeat::with(['booking', 'seat'])->find($id);
    }

    public function create(array $data): BookingSeat
    {
        return BookingSeat::create($data);
    }

    public function update(int $id, array $data): BookingSeat
    {
        $bookingSeat = BookingSeat::findOrFail($id);

        $bookingSeat->update($data);

        return $bookingSeat->fresh(['booking', 'seat']);
    }

    public function delete(int $id): bool
    {
        return BookingSeat::findOrFail($id)->delete();
    }

    public function byBooking(int $bookingId, array $filters = []): array
    {
        $query = BookingSeat::with(['seat'])
            ->where('booking_id', $bookingId);

        return $this->getPaginated($query, $filters);
    }
}
