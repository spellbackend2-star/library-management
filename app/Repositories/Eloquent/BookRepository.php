<?php

namespace App\Repositories\Eloquent;

use App\Models\Book;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\BookInterface;
use Illuminate\Support\Facades\DB;

class BookRepository extends BaseRepository implements BookInterface
{
    protected array $allowedSorts = [
        'id',
        'title',
        'language',
        'created_at',
    ];

    protected array $allowedFilters = [
        'search' => [
            'type' => 'like',
            'column' => 'title',
        ],
        'title' => [
            'type' => 'like',
            'column' => 'title',
        ],
        'subtitle' => [
            'type' => 'like',
            'column' => 'subtitle',
        ],
        'description' => [
            'type' => 'like',
            'column' => 'description',
        ],
        'language' => [
            'type' => 'exact',
            'column' => 'language',
        ],
        'category_id' => [
            'type' => 'exact',
            'column' => 'id',
            'relation' => 'categories',
        ],
        'author_id' => [
            'type' => 'exact',
            'column' => 'id',
            'relation' => 'authors',
        ],
    ];

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

    public function getAll(array $filters = []): array
    {
        $query = Book::query()->with([
            'authors',
            'categories',
            'editions.book',
            'editions.publisher',
            'editions.copies.edition.book',
        ]);

        // Filter by status (availability based on copies) - custom logic not covered by BaseRepository
        if (isset($filters['status']) && $filters['status'] !== '') {
            $status = strtolower($filters['status']);
            if ($status === 'available') {
                $query->whereHas('editions.copies', function ($q) {
                    $q->where('status', 'available');
                });
            } elseif ($status === 'unavailable') {
                $query->whereDoesntHave('editions.copies', function ($q) {
                    $q->where('status', 'available');
                })->whereHas('editions.copies');
            } elseif ($status === 'no_copies') {
                $query->whereDoesntHave('editions.copies');
            }
        }

        return $this->getPaginated($query, $filters);
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