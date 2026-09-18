<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Repositories\Interface\SubscriptionPlanInterface;

class SubscriptionPlanService
{
    public function __construct(
        protected SubscriptionPlanInterface $subscriptionPlanRepository
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->subscriptionPlanRepository->getAll($filters);
    }

    public function getById(int $id): ?SubscriptionPlan
    {
        return $this->subscriptionPlanRepository->find($id);
    }

    public function create(array $data): SubscriptionPlan
    {
        return $this->subscriptionPlanRepository->create($data);
    }

    public function update(int $id, array $data): SubscriptionPlan
    {
        return $this->subscriptionPlanRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->subscriptionPlanRepository->delete($id);
    }
}
