<?php

namespace App\Repositories\Eloquent;

use App\Models\SeatCategory;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\SeatCategoryInterface;

class SeatCategoryRepository extends BaseRepository implements SeatCategoryInterface
{
    protected array $allowedSorts = [
        'id',
        'name',
        'created_at',
        'updated_at',
    ];

    protected array $allowedFilters = [
        'search' => [
            'type' => 'like',
            'column' => 'name',
        ],
        'name' => [
            'type' => 'like',
            'column' => 'name',
        ],
    ];

    public function all()
    {
        return SeatCategory::latest()->get();
    }

    public function getAll(array $filters = []): array
    {
        $query = SeatCategory::query();

        return $this->getPaginated($query, $filters);
    }

    public function find(int $id): ?SeatCategory
    {
        return SeatCategory::find($id);
    }

    public function create(array $data): SeatCategory
    {
        return SeatCategory::create($data);
    }

    public function update(int $id, array $data): SeatCategory
    {
        $category = SeatCategory::findOrFail($id);

        $category->update($data);

        return $category->fresh();
    }

    public function delete(int $id): bool
    {
        return SeatCategory::findOrFail($id)->delete();
    }
}
