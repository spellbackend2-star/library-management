<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Interface\CategoryInterface;

class CategoryRepository implements CategoryInterface
{
    public function all()
    {
        return Category::latest()->get();
    }

    public function find(int $id): ?Category
    {
        return Category::find($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(int $id, array $data): Category
    {
        $category = Category::findOrFail($id);

        $category->update($data);

        return $category->fresh();
    }

    public function delete(int $id): bool
    {
        return Category::findOrFail($id)->delete();
    }
}
