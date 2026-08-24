<?php

namespace App\Repositories\Eloquent;

use App\Models\Author;
use App\Repositories\Interface\AuthorInterface;

class AuthorRepository implements AuthorInterface
{
    public function all()
    {
        return Author::latest()->get();
    }

    public function find(int $id): ?Author
    {
        return Author::find($id);
    }

    public function create(array $data): Author
    {
        return Author::create($data);
    }

    public function update(int $id, array $data): Author
    {
        $author = Author::findOrFail($id);

        $author->update($data);

        return $author->fresh();
    }

    public function delete(int $id): bool
    {
        return Author::findOrFail($id)->delete();
    }
}
