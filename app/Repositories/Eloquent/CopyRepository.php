<?php

namespace App\Repositories\Eloquent;

use App\Models\Copy;
use App\Repositories\Interface\CopyInterface;

class CopyRepository implements CopyInterface
{
    public function all()
    {
        return Copy::latest()->get();
    }

    public function find(int $id): ?Copy
    {
        return Copy::find($id);
    }

    public function create(array $data): Copy
    {
        return Copy::create($data);
    }

    public function update(int $id, array $data): Copy
    {
        $copy = Copy::findOrFail($id);

        $copy->update($data);

        return $copy->fresh();
    }

    public function delete(int $id): bool
    {
        return Copy::findOrFail($id)->delete();
    }
}
