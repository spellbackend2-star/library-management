<?php

namespace App\Repositories\Interface;

use App\Models\Book;

interface BookInterface
{
    public function all();

    public function find(int $id): ?Book;

    public function findOrFail(int $id): Book;

    public function findWithRelations(int $id): Book;

    public function create(array $data): Book;

    public function updateBook(Book $book, array $data): Book;

    public function delete(Book $book): bool;

    public function forceDelete(Book $book): bool;

    public function syncBookAuthors(
        Book $book,
        array $authorIds
    ): void;

    public function syncBookCategories(
        Book $book,
        array $categoryIds
    ): void;

    public function loadRelations(Book $book): Book;
}