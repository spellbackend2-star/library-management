<?php

namespace App\Repositories\Eloquent;

use App\Models\BookEdition;
use App\Repositories\Interface\BookEditionInterface;

class BookEditionRepository implements BookEditionInterface
{
    public function all()
    {
        return BookEdition::with([
            'book',
            'publisher',
            'copies.edition.book',
        ])->latest()->get();
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
