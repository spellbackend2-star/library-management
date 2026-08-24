<?php

namespace App\Repositories\Eloquent;

use App\Models\BookAuthor;
use App\Repositories\Interface\BookAuthorInterface;

class BookAuthorRepository implements BookAuthorInterface
{
    public function all()
    {
         return BookAuthor::all();
    }

    public function find(int $id): ?BookAuthor
    {
        return BookAuthor::find($id);
    }

    public function create(array $data): BookAuthor
    {
        return BookAuthor::create($data);
    }

    public function update(int $id, array $data): BookAuthor
    {
        $bookAuthor = BookAuthor::findOrFail($id);

        $bookAuthor->update($data);

        return $bookAuthor->fresh();
    }

    public function delete(int $id): bool
    {
        return BookAuthor::findOrFail($id)->delete();
    }
}
