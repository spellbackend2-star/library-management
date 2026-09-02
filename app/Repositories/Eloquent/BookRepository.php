<?php

namespace App\Repositories\Eloquent;

use App\Models\Book;
use App\Repositories\Interface\BookInterface;
use Illuminate\Support\Facades\DB;

class BookRepository implements BookInterface
{
    public function all()
    {
        return Book::with([
            'authors',
            'categories',
            'editions.book',
            'editions.publisher',
            'editions.copies.edition.book',
        ])
            ->latest()
            ->get();
    }

    public function find(int $id): ?Book
    {
        return Book::find($id);
    }

    public function findOrFail(int $id): Book
    {
        return Book::findOrFail($id);
    }

    public function findWithRelations(int $id): Book
    {
        return Book::with([
            'authors',
            'categories',
            'editions.book',
            'editions.publisher',
            'editions.copies.edition.book',
        ])->findOrFail($id);
    }

    public function create(array $data): Book
    {
        return Book::create($data);
    }

    public function updateBook(Book $book, array $data): Book
    {
        $book->update($data);

        return $book->fresh();
    }

    public function delete(Book $book): bool
    {
        return DB::transaction(function () use ($book) {

            $book->loadMissing(['editions.copies']);

            foreach ($book->editions as $edition) {

                foreach ($edition->copies as $copy) {

                    $copy->borrows()->delete();

                    $copy->delete();
                }

                $edition->delete();
            }

            $book->authors()->detach();
            $book->categories()->detach();

            return $book->delete();
        });
    }

    public function forceDelete(Book $book): bool
    {
        return $book->forceDelete();
    }

    public function syncBookAuthors(
        Book $book,
        array $authorIds
    ): void {
        $book->authors()->sync(
            array_unique(
                array_map('intval', $authorIds)
            )
        );
    }

    public function syncBookCategories(
        Book $book,
        array $categoryIds
    ): void {
        $book->categories()->sync(
            array_unique(
                array_map('intval', $categoryIds)
            )
        );
    }

    public function loadRelations(Book $book): Book
    {
        return $book->load([
            'authors',
            'categories',
            'editions.book',
            'editions.publisher',
            'editions.copies.edition.book',
        ]);
    }
}