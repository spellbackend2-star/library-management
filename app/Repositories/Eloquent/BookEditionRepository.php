<?php

namespace App\Repositories\Eloquent;

use App\Models\BookEdition;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\BookEditionInterface;

class BookEditionRepository extends BaseRepository implements BookEditionInterface
{
    protected array $allowedFilters = [
        'id' => [
            'type' => 'exact',
            'column' => 'id',
        ],
    ];

    public function all()
    {
        return BookEdition::with([
            'book',
            'publisher',
            'copies.edition.book',
        ])->latest()->get();
    }

    public function getAll(array $filters = []): array
    {
        $query = BookEdition::with([
            'book',
            'publisher',
            'copies.edition.book',
        ]);

        $paginator = $this->applyPagination(
            $this->applyFilters($query, $filters),
            $filters
        );

        return [
            'data' => $paginator->items(),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    public function find(int $id): ?BookEdition
    {
        return BookEdition::with([
            'book',
            'publisher',
            'copies.edition.book',
        ])->find($id);
    }

    public function create(array $data): BookEdition
    {
        return BookEdition::create($data);
    }

    public function update(int $id, array $data): BookEdition
    {
        $edition = BookEdition::findOrFail($id);

        $edition->update($data);

        return $edition->fresh();
    }

    public function delete(int $id): bool
    {
        return BookEdition::findOrFail($id)->delete();
    }

    public function deleteNotIn(int $bookId, array $keptIds): void
    {
        BookEdition::where('book_id', $bookId)
            ->whereNotIn('id', $keptIds)
            ->get()
            ->each(function ($edition) {
                $edition->copies()->delete();
                $edition->delete();
            });
    }
}
