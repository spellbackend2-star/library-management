<?php

namespace App\Services;

use App\Models\Book;
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

    public function getAll()
    {
        return $this->bookRepository->all();
    }

    public function getByIdWithRelations(int $id): ?Book
    {
        return $this->bookRepository->findWithRelations($id);
    }

    public function create(array $data): Book
    {
        return $this->bookRepository->create([
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'language' => $data['language'] ?? null,
            'description' => $data['description'] ?? null,
            'cover_image_url' => $data['cover_image_url'] ?? null,
        ]);
    }

    public function update(int $id, array $data): Book
    {
        $book = $this->bookRepository->findOrFail($id);

        $this->bookRepository->updateBook($book, [
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'language' => $data['language'] ?? null,
            'description' => $data['description'] ?? null,
            'cover_image_url' => $data['cover_image_url'] ?? null,
        ]);

        return $this->bookRepository->loadRelations($book);
    }

    public function updateWithRelations(int $id, array $data): Book
    {
        return DB::transaction(function () use ($id, $data) {

            $book = $this->bookRepository->findOrFail($id);

            $this->bookRepository->updateBook($book, [
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'language' => $data['language'] ?? null,
                'description' => $data['description'] ?? null,
                'cover_image_url' => $data['cover_image_url'] ?? null,
            ]);

            if (isset($data['author_ids'])) {
                $this->bookRepository->syncBookAuthors(
                    $book,
                    $data['author_ids']
                );
            }

            if (isset($data['category_ids'])) {
                $this->bookRepository->syncBookCategories(
                    $book,
                    $data['category_ids']
                );
            }

            if (isset($data['editions'])) {
                $keptEditionIds = [];

                foreach ($data['editions'] as $editionData) {

                    if (isset($editionData['id'])) {
                        $edition = $this->editionRepository->update(
                            $editionData['id'],
                            $editionData
                        );
                    } else {
                        $edition = $this->editionRepository->create([
                            'book_id' => $book->id,
                            'publisher_id' => $editionData['publisher_id'],
                            'isbn' => $editionData['isbn'] ?? null,
                            'edition_number' => $editionData['edition_number'] ?? null,
                            'publication_year' => $editionData['publication_year'] ?? null,
                            'format' => $editionData['format'] ?? 'physical',
                        ]);
                    }

                    $keptEditionIds[] = $edition->id;

                    if (isset($editionData['copies'])) {
                        $keptCopyIds = [];

                        foreach ($editionData['copies'] as $copyData) {

                            if (isset($copyData['id'])) {
                                $copy = $this->copyRepository->update(
                                    $copyData['id'],
                                    $copyData
                                );
                            } else {
                                $copy = $this->copyRepository->create([
                                    'edition_id' => $edition->id,
                                    'barcode' => $copyData['barcode'],
                                    'shelf_location' => $copyData['shelf_location'] ?? null,
                                    'condition' => $copyData['condition'] ?? 'new',
                                    'status' => $copyData['status'] ?? 'available',
                                    'acquisition_date' => $copyData['acquisition_date'] ?? now(),
                                ]);
                            }

                            $keptCopyIds[] = $copy->id;
                        }

                        $this->copyRepository->deleteNotIn(
                            $edition->id,
                            $keptCopyIds
                        );
                    }
                }

                $this->editionRepository->deleteNotIn(
                    $book->id,
                    $keptEditionIds
                );
            }

            $book->load([
                'authors',
                'categories',
                'editions.book',
                'editions.publisher',
                'editions.copies.edition.book',
            ]);

            return $book;
        });
    }

    public function delete(int $id): bool
    {
        $book = $this->bookRepository->findWithRelations($id);

        return $this->bookRepository->delete($book);
    }

    public function createWithRelations(array $data)
    {
        return DB::transaction(function () use ($data) {

            // 1. Create book
            $book = $this->bookRepository->create([
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'language' => $data['language'] ?? null,
                'description' => $data['description'] ?? null,
                'cover_image_url' => $data['cover_image_url'] ?? null,
            ]);

            // 2. Attach existing authors
            if (isset($data['author_ids'])) {
                $this->bookRepository->syncBookAuthors(
                    $book,
                    $data['author_ids']
                );
            }

            // 3. Attach existing categories
            if (isset($data['category_ids'])) {
                $this->bookRepository->syncBookCategories(
                    $book,
                    $data['category_ids']
                );
            }

            // 4. Create editions
            foreach ($data['editions'] ?? [] as $editionData) {

                $edition = $this->editionRepository->create([
                    'book_id' => $book->id,
                    'publisher_id' => $editionData['publisher_id'],
                    'isbn' => $editionData['isbn'] ?? null,
                    'edition_number' =>
                    $editionData['edition_number'] ?? null,
                    'publication_year' =>
                    $editionData['publication_year'] ?? null,
                    'format' => $editionData['format'] ?? null,
                ]);

                // 5. Create copies
                foreach ($editionData['copies'] ?? [] as $copyData) {

                    $this->copyRepository->create([
                        'edition_id' => $edition->id,
                        'barcode' => $copyData['barcode'],
                        'shelf_location' => $copyData['shelf_location'] ?? null,
                        'condition' => $copyData['condition'] ?? 'new',
                        'status' => $copyData['status'] ?? 'available',
                        'acquisition_date' => $copyData['acquisition_date'] ?? now(),
                    ]);
                }
            }

            return $this->bookRepository
                ->loadRelations($book);
        });
    }
}
