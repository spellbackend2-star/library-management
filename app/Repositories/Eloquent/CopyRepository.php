<?php

namespace App\Repositories\Eloquent;

use App\Models\Copy;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\CopyInterface;

class CopyRepository extends BaseRepository implements CopyInterface
{
    private const COPY_RELATIONS = [
        'edition.book',
        'currentBorrows.member',
        'currentBorrows.staffBorrower',
    ];

    protected array $allowedFilters = [
        'id' => [
            'type' => 'exact',
            'column' => 'id',
        ],
        'barcode' => [
            'type' => 'exact',
            'column' => 'barcode',
        ],
    ];

    public function all()
    {
        return Copy::with(self::COPY_RELATIONS)->latest()->get();
    }

    public function getAll(array $filters = []): array
    {
        $query = Copy::with(self::COPY_RELATIONS);

        $paginator = $this->applyPagination(
            $this->applyFilters($query, $filters),
            $filters
        );

        return [
            'data' => $paginator->items(),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    public function getForBook(int $bookId, array $filters = []): array
    {
        $query = Copy::with(self::COPY_RELATIONS)
            ->whereHas('edition', function ($query) use ($bookId) {
                $query->where('book_id', $bookId);
            })
            ->latest();

        $paginator = $this->applyPagination(
            $this->applyFilters($query, $filters),
            $filters
        );

        return [
            'data' => $paginator->items(),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    public function find(int $id): ?Copy
    {
        return Copy::with(self::COPY_RELATIONS)->find($id);
    }

    public function create(array $data): Copy
    {
        return Copy::create($data);
    }

    public function update(int $id, array $data): Copy
    {
        $copy = Copy::findOrFail($id);

        $copy->update($data);

        return $copy->fresh(self::COPY_RELATIONS);
    }

    public function delete(int $id): bool
    {
        return Copy::findOrFail($id)->delete();
    }
     public function findForEdition(int $editionId, int $copyId): Copy
    {
        return Copy::where('edition_id', $editionId)
            ->where('id', $copyId)
            ->firstOrFail();
    }

    public function deleteNotIn(int $editionId, array $keptIds): void
    {
        Copy::where('edition_id', $editionId)
            ->whereNotIn('id', $keptIds)
            ->delete();
    }
}
