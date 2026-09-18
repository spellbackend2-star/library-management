<?php

namespace App\Repositories\Interface;

use App\Models\SubscriptionPlan;

interface SubscriptionPlanInterface
{
    public function all();

    public function getAll(array $filters = []);

    public function find(int $id): ?SubscriptionPlan;

    public function create(array $data): SubscriptionPlan;

    public function update(int $id, array $data): SubscriptionPlan;

    public function delete(int $id): bool;
}
