<?php

namespace App\Repositories\Eloquent;

use App\Models\Seat;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\SeatInterface;

class SeatRepository extends BaseRepository implements SeatInterface
{
    protected array $allowedSorts = [
        'id',
        'seat_number',
        'status',
        'created_at',
        'updated_at',
    ];

    protected array $allowedFilters = [
        'room_id' => [
            'type' => 'exact',
            'column' => 'room_id',
        ],
        'category_id' => [
            'type' => 'exact',
            'column' => 'category_id',
        ],
        'status' => [
            'type' => 'exact',
            'column' => 'status',
        ],
        'has_power_outlet' => [
            'type' => 'exact',
            'column' => 'has_power_outlet',
        ],
        'is_accessible' => [
            'type' => 'exact',
            'column' => 'is_accessible',
        ],
        'search' => [
            'type' => 'like',
            'column' => 'seat_number',
        ],
    ];

    public function all()
    {
        return Seat::with(['room', 'category'])->latest()->get();
    }

    public function getAll(array $filters = []): array
    {
        $query = Seat::with(['room', 'category']);

        return $this->getPaginated($query, $filters);
    }

    public function find(int $id): ?Seat
    {
        return Seat::with(['room', 'category'])->find($id);
    }

    public function create(array $data): Seat
    {
        return Seat::create($data);
    }

    public function update(int $id, array $data): Seat
    {
        $seat = Seat::findOrFail($id);

        $seat->update($data);

        return $seat->fresh();
    }

    public function delete(int $id): bool
    {
        return Seat::findOrFail($id)->delete();
    }
}
