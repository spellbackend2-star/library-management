<?php

namespace App\Repositories\Eloquent;

use App\Models\Publisher;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\PublisherInterface;

class PublisherRepository extends BaseRepository implements PublisherInterface
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
        return Publisher::latest()->get();
    }

    public function getAll(array $filters = []): array
    {
        $query = Publisher::query();

        return $this->getPaginated($query, $filters);
    }

    public function find(int $id): ?Publisher
    {
        return Publisher::find($id);
    }

    public function create(array $data): Publisher
    {
        return Publisher::create($data);
    }

    public function update(int $id, array $data): Publisher
    {
        $publisher = Publisher::findOrFail($id);

        $publisher->update($data);

        return $publisher->fresh();
    }

    public function delete(int $id): bool
    {
        return Publisher::findOrFail($id)->delete();
    }
}
