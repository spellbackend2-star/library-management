<?php

namespace App\Repositories\Eloquent;

use App\Models\SeatCategory;
use App\Repositories\Interface\SeatCategoryInterface;

class SeatCategoryRepository implements SeatCategoryInterface
{
    public function all()
    {
        return SeatCategory::latest()->get();
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
