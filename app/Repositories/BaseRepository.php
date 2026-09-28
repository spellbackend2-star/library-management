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
        $perPage = max(
            1,
            min((int) ($filters['per_page'] ?? 15), 100)
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
            $columns = $config['columns'] ?? [$column];
            $relation = $config['relation'] ?? null;

            switch ($type) {
                case 'like':
                    if ($relation) {
                        $query->whereHas($relation, function ($q) use ($columns, $value) {
                            $this->applyLike($q, $columns, $value);
                        });
                    } else {
                        $this->applyLike($query, $columns, $value);
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
                case 'boolean':
                    $boolean = filter_var(
                        $value,
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE
                    );

                    if ($boolean === null) {
                        break;
                    }

                    if ($relation) {
                        $query->whereHas($relation, function ($q) use ($column, $boolean) {
                            $q->where($column, $boolean);
                        });
                    } else {
                        $query->where($column, $boolean);
                    }
                    break;
                case 'min':
                    if ($relation) {
                        $query->whereHas($relation, function ($q) use ($column, $value) {
                            $q->where($column, '>=', (float) $value);
                        });
                    } else {
                        $query->where($column, '>=', (float) $value);
                    }
                    break;
                case 'max':
                    if ($relation) {
                        $query->whereHas($relation, function ($q) use ($column, $value) {
                            $q->where($column, '<=', (float) $value);
                        });
                    } else {
                        $query->where($column, '<=', (float) $value);
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

    /**
     * Apply a "like" match across one or many columns as a single
     * grouped OR condition.
     */
    protected function applyLike(
        Builder $query,
        array $columns,
        mixed $value
    ): void {
        $needle = '%' . $value . '%';

        if (count($columns) === 1) {
            $query->where($columns[0], 'like', $needle);

            return;
        }

        $query->where(function ($q) use ($columns, $needle) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', $needle);
            }
        });
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