<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Copy;
use App\Repositories\Interface\BookInterface;
use App\Repositories\Interface\BookEditionInterface;
use App\Repositories\Interface\CopyInterface;
use Illuminate\Support\Facades\DB;

class BookService
{
    public function __construct(
        protected BookInterface $bookRepository,
        protected BookEditionInterface $editionRepository,
        protected CopyInterface $copyRepository,
    ) {}

    /**
     * Get all books.
     */
    public function getAll()
    {
        return $this->bookRepository->all();
    }

    /**
     * Get single book with relations.
     */
    public function getByIdWithRelations(int $id): ?Book
    {
        return $this->bookRepository->findWithRelations($id);
    }

    /**
     * Create book with authors, categories, editions and copies.
     */
    public function createWithRelations(array $data): Book
    {
        return DB::transaction(function () use ($data) {

            // Create book
            $book = $this->bookRepository->create([
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'language' => $data['language'] ?? null,
                'description' => $data['description'] ?? null,
                'cover_image_url' => $data['cover_image_url'] ?? null,
            ]);

            // Authors
            if (isset($data['author_ids'])) {
                $this->bookRepository->syncBookAuthors(
                    $book,
                    $data['author_ids']
                );
            }

            // Categories
            if (isset($data['category_ids'])) {
                $this->bookRepository->syncBookCategories(
                    $book,
                    $data['category_ids']
                );
            }

            // Editions
            foreach ($data['editions'] ?? [] as $editionData) {

                $edition = $this->editionRepository->create([
                    'book_id' => $book->id,
                    'publisher_id' => $editionData['publisher_id'],
                    'isbn' => $editionData['isbn'] ?? null,
                    'edition_number' => $editionData['edition_number'] ?? null,
                    'publication_year' => $editionData['publication_year'] ?? null,
                    'format' => $editionData['format'] ?? 'physical',
                ]);

                // Copies
                foreach ($editionData['copies'] ?? [] as $copyData) {

                    $this->copyRepository->create([
                        'edition_id' => $edition->id,
                        'barcode' => $copyData['barcode'],
                        'shelf_location' => $copyData['shelf_location'] ?? null,
                        'condition' => $copyData['condition'] ?? 'new',
                        'status' => $copyData['status'] ?? 'available',
                        'acquisition_date' =>
                            $copyData['acquisition_date'] ?? now(),
                    ]);
                }
            }

            return $this->bookRepository->loadRelations($book);
        });
    }

    /**
     * Update book with authors, categories, editions and copies.
     */
    public function updateWithRelations(int $id, array $data): Book
    {
        return DB::transaction(function () use ($id, $data) {

            $book = $this->bookRepository->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Update Book
            |--------------------------------------------------------------------------
            */

            $this->bookRepository->updateBook($book, [
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'language' => $data['language'] ?? null,
                'description' => $data['description'] ?? null,
                'cover_image_url' => $data['cover_image_url'] ?? null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Authors
            |--------------------------------------------------------------------------
            */

            if (isset($data['author_ids'])) {
                $this->bookRepository->syncBookAuthors(
                    $book,
                    $data['author_ids']
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Categories
            |--------------------------------------------------------------------------
            */

            if (isset($data['category_ids'])) {
                $this->bookRepository->syncBookCategories(
                    $book,
                    $data['category_ids']
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Editions
            |--------------------------------------------------------------------------
            */

            if (isset($data['editions'])) {

                $keptEditionIds = [];

                foreach ($data['editions'] as $editionData) {

                    /*
                    |--------------------------------------------------------------------------
                    | Existing Edition
                    |--------------------------------------------------------------------------
                    */

                    if (isset($editionData['id'])) {

                        $edition = $this->editionRepository->update(
                            $editionData['id'],
                            [
                                'publisher_id' =>
                                    $editionData['publisher_id'],

                                'isbn' =>
                                    $editionData['isbn'] ?? null,

                                'edition_number' =>
                                    $editionData['edition_number'] ?? null,

                                'publication_year' =>
                                    $editionData['publication_year'] ?? null,

                                'format' =>
                                    $editionData['format'] ?? 'physical',
                            ]
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | New Edition
                    |--------------------------------------------------------------------------
                    */

                    } else {

                        $edition = $this->editionRepository->create([
                            'book_id' => $book->id,
                            'publisher_id' =>
                                $editionData['publisher_id'],

                            'isbn' =>
                                $editionData['isbn'] ?? null,

                            'edition_number' =>
                                $editionData['edition_number'] ?? null,

                            'publication_year' =>
                                $editionData['publication_year'] ?? null,

                            'format' =>
                                $editionData['format'] ?? 'physical',
                        ]);
                    }

                    $keptEditionIds[] = $edition->id;

                    /*
                    |--------------------------------------------------------------------------
                    | Copies
                    |--------------------------------------------------------------------------
                    */

                    if (isset($editionData['copies'])) {

                        $keptCopyIds = [];

                        foreach ($editionData['copies'] as $copyData) {

                            /*
                            |--------------------------------------------------------------------------
                            | Existing Copy
                            |--------------------------------------------------------------------------
                            */

                            if (isset($copyData['id'])) {

                                $copy = $this->copyRepository->update(
                                    $copyData['id'],
                                    [
                                        'barcode' =>
                                            $copyData['barcode'],

                                        'shelf_location' =>
                                            $copyData['shelf_location'] ?? null,

                                        'condition' =>
                                            $copyData['condition'] ?? 'new',

                                        'status' =>
                                            $copyData['status'] ?? 'available',

                                        'acquisition_date' =>
                                            $copyData['acquisition_date'] ?? null,
                                    ]
                                );

                            /*
                            |--------------------------------------------------------------------------
                            | New Copy
                            |--------------------------------------------------------------------------
                            */

                            } else {

                                $copy = $this->copyRepository->create([
                                    'edition_id' => $edition->id,
                                    'barcode' =>
                                        $copyData['barcode'],

                                    'shelf_location' =>
                                        $copyData['shelf_location'] ?? null,

                                    'condition' =>
                                        $copyData['condition'] ?? 'new',

                                    'status' =>
                                        $copyData['status'] ?? 'available',

                                    'acquisition_date' =>
                                        $copyData['acquisition_date'] ?? now(),
                                ]);
                            }

                            $keptCopyIds[] = $copy->id;
                        }

                        // Delete removed copies
                        $this->copyRepository->deleteNotIn(
                            $edition->id,
                            $keptCopyIds
                        );
                    }
                }

                // Delete removed editions
                $this->editionRepository->deleteNotIn(
                    $book->id,
                    $keptEditionIds
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Reload Relations
            |--------------------------------------------------------------------------
            */

            return $book->load([
                'authors',
                'categories',
                'editions.book',
                'editions.publisher',
                'editions.copies.edition.book',
            ]);
        });
    }

    /**
     * Add one or more copies to an existing edition of a book.
     */
    public function addCopies(int $bookId, int $editionId, array $copies): array
    {
        return DB::transaction(function () use ($bookId, $editionId, $copies) {
            $book = $this->bookRepository->findOrFail($bookId);

            $edition = $this->editionRepository->find($editionId);

            if (!$edition || $edition->book_id !== $book->id) {
                throw new \Exception('Edition does not belong to this book.');
            }

            $created = [];

            foreach ($copies as $copyData) {
                $created[] = $this->copyRepository->create([
                    'edition_id' => $edition->id,
                    'barcode' => $copyData['barcode'],
                    'shelf_location' => $copyData['shelf_location'] ?? null,
                    'condition' => $copyData['condition'] ?? 'new',
                    'status' => $copyData['status'] ?? 'available',
                    'acquisition_date' =>
                        $copyData['acquisition_date'] ?? now()->toDateString(),
                ]);
            }

            return [
                'book' => $book->load([
                    'authors',
                    'categories',
                    'editions.publisher',
                    'editions.copies',
                ]),
                'created_copies' => $created,
            ];
        });
    }

    /**
     * Get a single copy that belongs to the book.
     */
    public function getCopy(int $bookId, int $copyId): Copy
    {
        $book = $this->bookRepository->findOrFail($bookId);

        $copy = $this->copyRepository->find($copyId);

        if (!$copy) {
            throw new \Exception('Copy not found.');
        }

        $edition = $this->editionRepository->find($copy->edition_id);

        if (!$edition || $edition->book_id !== $book->id) {
            throw new \Exception('Copy does not belong to this book.');
        }

        return $copy;
    }

    /**
     * List all copies that belong to the book (across all editions).
     */
    public function listCopies(int $bookId)
    {
        $book = $this->bookRepository->findOrFail($bookId);

        return Copy::with(['edition'])
            ->whereHas('edition', function ($q) use ($book) {
                $q->where('book_id', $book->id);
            })
            ->latest()
            ->get();
    }

    /**
     * Update an existing copy that belongs to one of the book's editions.
     */
    public function updateCopy(int $bookId, int $copyId, array $data): Copy
    {
        return DB::transaction(function () use ($bookId, $copyId, $data) {
            $book = $this->bookRepository->findOrFail($bookId);

            $copy = $this->copyRepository->find($copyId);

            if (!$copy) {
                throw new \Exception('Copy not found.');
            }

            $edition = $this->editionRepository->find($copy->edition_id);

            if (!$edition || $edition->book_id !== $book->id) {
                throw new \Exception('Copy does not belong to this book.');
            }

            return $this->copyRepository->update($copyId, $data);
        });
    }

    /**
     * Delete a copy that belongs to one of the book's editions.
     */
    public function deleteCopy(int $bookId, int $copyId): bool
    {
        return DB::transaction(function () use ($bookId, $copyId) {
            $book = $this->bookRepository->findOrFail($bookId);

            $copy = $this->copyRepository->find($copyId);

            if (!$copy) {
                throw new \Exception('Copy not found.');
            }

            $edition = $this->editionRepository->find($copy->edition_id);

            if (!$edition || $edition->book_id !== $book->id) {
                throw new \Exception('Copy does not belong to this book.');
            }

            return $this->copyRepository->delete($copyId);
        });
    }

    /**
     * Delete complete book.
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            $book = $this->bookRepository->findWithRelations($id);

            return $this->bookRepository->delete($book);
        });
    }
}
