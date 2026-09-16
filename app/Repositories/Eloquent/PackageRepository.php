<?php

namespace App\Repositories\Eloquent;

use App\Models\Package;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\PackageInterface;

class PackageRepository extends BaseRepository implements PackageInterface
{
    protected array $allowedSorts = [
        'id',
        'name',
        'price',
        'duration',
        'duration_unit',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public function all()
    {
        return Package::latest()->get();
    }

    public function getAll(array $filters = [])
    {
        $query = Package::query();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if (
            isset($filters['search'])
            && $filters['search'] !== ''
        ) {
            $query->where(function ($q) use ($filters) {
                $q->where(
                    'name',
                    'like',
                    '%' . $filters['search'] . '%'
                )
                ->orWhere(
                    'description',
                    'like',
                    '%' . $filters['search'] . '%'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Active Status
        |--------------------------------------------------------------------------
        */
        if (
            array_key_exists('is_active', $filters)
            && $filters['is_active'] !== null
            && $filters['is_active'] !== ''
        ) {
            $query->where(
                'is_active',
                $this->toBool($filters['is_active'])
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Duration Unit
        |--------------------------------------------------------------------------
        */
        if (
            isset($filters['duration_unit'])
            && $filters['duration_unit'] !== ''
        ) {
            $query->where(
                'duration_unit',
                $filters['duration_unit']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Minimum Price
        |--------------------------------------------------------------------------
        */
        if (
            isset($filters['min_price'])
            && $filters['min_price'] !== ''
        ) {
            $query->where(
                'price',
                '>=',
                (float) $filters['min_price']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Maximum Price
        |--------------------------------------------------------------------------
        */
        if (
            isset($filters['max_price'])
            && $filters['max_price'] !== ''
        ) {
            $query->where(
                'price',
                '<=',
                (float) $filters['max_price']
            );
        }

        if (
            !isset($filters['sort_by'])
            || !in_array($filters['sort_by'], $this->allowedSorts, true)
        ) {
            $query->latest('id');
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */
        return $this->getPaginated(
            $query,
            $filters
        );
    }

    protected function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function find(int $id): ?Package
    {
        return Package::find($id);
    }

    public function create(array $data): Package
    {
        return Package::create($data);
    }

    public function update(
        int $id,
        array $data
    ): Package {
        $package = Package::findOrFail($id);

        $package->update($data);

        return $package->fresh();
    }

    public function delete(int $id): bool
    {
        return Package::findOrFail($id)->delete();
    }
}
