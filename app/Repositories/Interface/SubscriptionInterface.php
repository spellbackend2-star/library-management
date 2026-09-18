<?php

namespace App\Repositories\Interface;

use App\Models\Subscription;

interface SubscriptionInterface
{
    public function all();

    public function getAll(array $filters = []);

    public function find(int $id): ?Subscription;

    public function create(array $data): Subscription;

    public function update(int $id, array $data): Subscription;

    public function delete(int $id): bool;
}
