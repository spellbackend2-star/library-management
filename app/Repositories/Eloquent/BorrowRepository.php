<?php

namespace App\Repositories\Eloquent;

use App\Models\Borrow;
use App\Repositories\Interface\BorrowInterface;

class BorrowRepository implements BorrowInterface
{
    public function all()
    {
        return Borrow::latest()->get();
    }

    public function find(int $id): ?Borrow
    {
        return Borrow::find($id);
    }

    public function create(array $data): Borrow
    {
        return Borrow::create($data);
    }

    public function update(int $id, array $data): Borrow
    {
        $borrow = Borrow::findOrFail($id);

        $borrow->update($data);

        return $borrow->fresh();
    }

    public function delete(int $id): bool
    {
        return Borrow::findOrFail($id)->delete();
    }
}
