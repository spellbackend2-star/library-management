<?php

namespace App\Repositories\Eloquent;

use App\Models\Borrow;
use App\Models\Fine;
use App\Repositories\Interface\FineInterface;
use Illuminate\Database\Eloquent\Builder;

class FineRepository implements FineInterface
{
    private function query(): Builder
    {
        return Fine::query()->with([
            'member',
            'staff',
            'invoice',
            'borrow.copy.edition.book',
            'bookingSeat.seat',
            'lockerAssignment.locker',
            'creator',
            'adjustments',
        ]);
    }

    public function getAll(array $filters = []): array
    {
        $query = $this->query();

        foreach (['member_id', 'staff_id', 'status', 'invoice_id'] as $filter) {
            if (isset($filters[$filter]) && $filters[$filter] !== '') {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (filter_var($filters['all'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $fines = $query->get();
            $count = $fines->count();

            return [
                'data' => $fines,
                'meta' => [
                    'total' => $count,
                    'current_page' => 1,
                    'per_page' => $count,
                    'last_page' => 1,
                    'first_page_url' => null,
                    'last_page_url' => null,
                    'next_page_url' => null,
                    'prev_page_url' => null,
                ],
            ];
        }

        $paginator = $query
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();

        return [
            'data' => $paginator->getCollection(),
            'meta' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'first_page_url' => $paginator->url(1),
                'last_page_url' => $paginator->url($paginator->lastPage()),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ],
        ];
    }

    public function find(int $id): ?Fine
    {
        return $this->query()->find($id);
    }

    public function findBorrow(int $borrowId): ?Borrow
    {
        return Borrow::query()
            ->with(['member', 'staffBorrower'])
            ->find($borrowId);
    }
}
