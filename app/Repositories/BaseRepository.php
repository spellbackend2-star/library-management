<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    protected array $allowedSorts = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected function applyPagination(
        Builder $query,
        array $filters = []
    ) {
        $perPage = min(
            (int) ($filters['per_page'] ?? 15),
            100
        );

        return $query
            ->paginate($perPage)
            ->withQueryString();
    }

    protected function applySorting(
        Builder $query,
        array $filters = []
    ): Builder {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = $filters['sort_order'] ?? 'desc';

        if (
            $sortBy &&
            in_array($sortBy, $this->allowedSorts, true)
        ) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query;
    }

    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'first_page_url' => $paginator->url(1),
            'last_page_url' => $paginator->url($paginator->lastPage()),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
        ];
    }
}