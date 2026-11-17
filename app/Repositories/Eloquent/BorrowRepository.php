<?php

namespace App\Repositories\Eloquent;

use App\Models\Borrow;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\BorrowInterface;

class BorrowRepository extends BaseRepository implements BorrowInterface
{
    public function all(array $filters = [])
    {
        $query = Borrow::with(['copy.edition.book', 'member'])->latest();
        $paginator = $this->applyPagination($query, $filters);

        return [
            'data' => $paginator->items(),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    public function find(int $id): ?Borrow
    {
        return Borrow::with(['copy.edition.book', 'member'])->find($id);
    }

    public function create(array $data): Borrow
    {
        return Borrow::create($data)->load(['copy.edition.book', 'member']);
    }

    public function update(int $id, array $data): Borrow
    {
        $borrow = Borrow::findOrFail($id);

        $borrow->update($data);

        return $borrow->fresh(['copy.edition.book', 'member']);
    }

    public function delete(int $id): bool
    {
        return Borrow::findOrFail($id)->delete();
    }

    public function byBooking(int $bookingId)
    {
        return Borrow::with(['copy.edition.book', 'member'])
            ->where('booking_id', $bookingId)
            ->latest()
            ->get();
    }
}
