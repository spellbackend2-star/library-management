<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    protected array $allowedSorts = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected array $allowedFilters = [];

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

    protected function applyFilters(Builder $query, array $filters = []): Builder
    {
        foreach ($this->allowedFilters as $filter => $config) {
            if (!isset($filters[$filter]) || $filters[$filter] === '') {
                continue;
            }

            $value = $filters[$filter];
            $type = $config['type'] ?? 'exact';
            $column = $config['column'] ?? $filter;
            $relation = $config['relation'] ?? null;

            switch ($type) {
                case 'like':
                    if ($relation) {
                        $query->whereHas($relation, function ($q) use ($column, $value) {
                            $q->where($column, 'like', '%' . $value . '%');
                        });
                    } else {
                        $query->where($column, 'like', '%' . $value . '%');
                    }
                    break;
                case 'in':
                    $values = is_array($value) ? $value : explode(',', $value);
                    if ($relation) {
                        $query->whereHas($relation, function ($q) use ($column, $values) {
                            $q->whereIn($column, $values);
                        });
                    } else {
                        $query->whereIn($column, $values);
                    }
                    break;
                case 'exact':
                default:
                    if ($relation) {
                        $query->whereHas($relation, function ($q) use ($column, $value) {
                            $q->where($column, $value);
                        });
                    } else {
                        $query->where($column, $value);
                    }
                    break;
            }
        }

        return $query;
    }

    protected function getPaginated(
        Builder $query,
        array $filters = []
    ): array {
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters);
        $paginator = $this->applyPagination($query, $filters);

        return [
            'data' => $paginator->items(),
            'meta' => $this->paginationMeta($paginator),
        ];
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