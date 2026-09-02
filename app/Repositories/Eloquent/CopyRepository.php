<?php

namespace App\Repositories\Eloquent;

use App\Models\Copy;
use App\Repositories\Interface\CopyInterface;

class CopyRepository implements CopyInterface
{
    public function all()
    {
        return Copy::with(['edition.book'])->latest()->get();
    }

    public function find(int $id): ?Copy
    {
        return Copy::with(['edition.book'])->find($id);
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
     public function findForEdition(int $editionId, int $copyId): Copy
    {
        return Copy::where('edition_id', $editionId)
            ->where('id', $copyId)
            ->firstOrFail();
    }

    public function deleteNotIn(int $editionId, array $keptIds): void
    {
        Copy::where('edition_id', $editionId)
            ->whereNotIn('id', $keptIds)
            ->delete();
    }
}
