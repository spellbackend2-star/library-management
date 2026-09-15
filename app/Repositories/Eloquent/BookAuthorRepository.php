<?php

namespace App\Repositories\Eloquent;

use App\Models\BookAuthor;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\BookAuthorInterface;

class BookAuthorRepository extends BaseRepository implements BookAuthorInterface
{
    protected array $allowedFilters = [
        'id' => [
            'type' => 'exact',
            'column' => 'id',
        ],
    ];

    public function all()
    {
         return BookAuthor::all();
    }

    public function getAll(array $filters = []): array
    {
        $query = BookAuthor::query();

        $paginator = $this->applyPagination(
            $this->applyFilters($query, $filters),
            $filters
        );

        return [
            'data' => $paginator->items(),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    public function find(int $id): ?BookAuthor
    {
        return BookAuthor::find($id);
    }

    public function create(array $data): BookAuthor
    {
        return BookAuthor::create($data);
    }

    public function update(int $id, array $data): BookAuthor
    {
        $bookAuthor = BookAuthor::findOrFail($id);

        $bookAuthor->update($data);

        return $bookAuthor->fresh();
    }

    public function delete(int $id): bool
    {
        return BookAuthor::findOrFail($id)->delete();
    }
}
