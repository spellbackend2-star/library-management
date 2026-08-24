<?php

namespace App\Repositories\Eloquent;

use App\Models\Book;
use App\Repositories\Interface\BookInterface;

class BookRepository implements BookInterface
{
    public function all()
    {
        return Book::latest()->get();
    }

    public function find(int $id): ?Book
    {
        return Book::find($id);
    }

    public function create(array $data): Book
    {
        return Book::create($data);
    }

    public function update(int $id, array $data): Book
    {
        $book = Book::findOrFail($id);

        $book->update($data);

        return $book->fresh();
    }

    public function delete(int $id): bool
    {
        $book = Book::findOrFail($id);

        $book->delete();

        return true;
    }
}
