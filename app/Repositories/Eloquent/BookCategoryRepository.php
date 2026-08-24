<?php

namespace App\Repositories\Eloquent;

use App\Models\BookCategory;
use App\Repositories\Interface\BookCategoryInterface;

class BookCategoryRepository implements BookCategoryInterface
{
    public function all()
    {
        return BookCategory::latest()->get();
    }

    public function find(int $id): ?BookCategory
    {
        return BookCategory::find($id);
    }

    public function create(array $data): BookCategory
    {
        return BookCategory::create($data);
    }

    public function update(int $id, array $data): BookCategory
    {
        $bookCategory = BookCategory::findOrFail($id);

        $bookCategory->update($data);

        return $bookCategory->fresh();
    }

    public function delete(int $id): bool
    {
        return BookCategory::findOrFail($id)->delete();
    }
}
