<?php

namespace App\Repositories\Eloquent;

use App\Models\Package;
use App\Repositories\Interface\PackageInterface;

class PackageRepository implements PackageInterface
{
    public function all()
    {
        return Package::latest()->get();
    }

    public function find(int $id): ?Package
    {
        return Package::find($id);
    }

    public function create(array $data): Package
    {
        return Package::create($data);
    }

    public function update(int $id, array $data): Package
    {
        $package = Package::findOrFail($id);

        $package->update($data);

        return $package->fresh();
    }

    public function delete(int $id): bool
    {
        return Package::findOrFail($id)->delete();
    }
}
