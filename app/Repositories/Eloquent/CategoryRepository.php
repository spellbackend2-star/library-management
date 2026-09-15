<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\CategoryInterface;

class CategoryRepository extends BaseRepository implements CategoryInterface
{
    protected array $allowedSorts = [
        'id',
        'name',
        'created_at',
        'updated_at',
    ];

    protected array $allowedFilters = [
        'id' => [
            'type' => 'exact',
            'column' => 'id',
        ],
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
        return Category::latest()->get();
    }

    public function getAll(array $filters = []): array
    {
        $query = Category::query();

        return $this->getPaginated($query, $filters);
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
