<?php

namespace App\Repositories\Eloquent;

use App\Models\Floor;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\FloorInterface;

class FloorRepository extends BaseRepository implements FloorInterface
{
    protected array $allowedFilters = [
        'id' => [
            'type' => 'exact',
            'column' => 'id',
        ],
    ];

    public function all()
    {
        return Floor::latest()->get();
    }

    public function getAll(array $filters = []): array
    {
        $paginator = $this->applyPagination(
            $this->applyFilters(Floor::query(), $filters),
            $filters
        );

        return [
            'data' => $paginator->items(),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    public function find(int $id): ?Floor
    {
        return Floor::find($id);
    }

    public function create(array $data): Floor
    {
        return Floor::create($data);
    }

    public function update(int $id, array $data): Floor
    {
        $floor = Floor::findOrFail($id);

        $floor->update($data);

        return $floor->fresh();
    }

    public function delete(int $id): bool
    {
        return Floor::findOrFail($id)->delete();
    }
}
