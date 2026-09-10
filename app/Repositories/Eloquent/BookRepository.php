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

    public function getAll(array $filters = [])
    {
        $query = Book::query()->with([
            'authors',
            'categories',
            'editions.book',
            'editions.publisher',
            'editions.copies.edition.book',
        ]);

        // Search by title
        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('subtitle', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Filter by category
        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('categories.id', $filters['category_id']);
            });
        }

        // Filter by author
        if (isset($filters['author_id']) && $filters['author_id'] !== '') {
            $query->whereHas('authors', function ($q) use ($filters) {
                $q->where('authors.id', $filters['author_id']);
            });
        }

        // Filter by language
        if (isset($filters['language']) && $filters['language'] !== '') {
            $query->where('language', $filters['language']);
        }

        // Filter by status (availability based on copies)
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

        $query = $this->applySorting($query, $filters);

        $paginator = $this->applyPagination($query, $filters);

        return [
            'data' => $paginator->items(),
            'meta' => $this->paginationMeta($paginator),
        ];
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