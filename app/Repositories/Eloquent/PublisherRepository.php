<?php

namespace App\Repositories\Eloquent;

use App\Models\Publisher;
use App\Repositories\Interface\PublisherInterface;

class PublisherRepository implements PublisherInterface
{
    public function all()
    {
        return Publisher::latest()->get();
    }

    public function find(int $id): ?Publisher
    {
        return Publisher::find($id);
    }

    public function create(array $data): Publisher
    {
        return Publisher::create($data);
    }

    public function update(int $id, array $data): Publisher
    {
        $publisher = Publisher::findOrFail($id);

        $publisher->update($data);

        return $publisher->fresh();
    }

    public function delete(int $id): bool
    {
        return Publisher::findOrFail($id)->delete();
    }
}
