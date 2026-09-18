<?php

namespace App\Services;

use App\Models\Subscription;
use App\Repositories\Interface\SubscriptionInterface;

class SubscriptionService
{
    public function __construct(
        protected SubscriptionInterface $subscriptionRepository
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->subscriptionRepository->getAll($filters);
    }

    public function getById(int $id): ?Subscription
    {
        return $this->subscriptionRepository->find($id);
    }

    public function create(array $data): Subscription
    {
        return $this->subscriptionRepository->create($data);
    }

    public function update(int $id, array $data): Subscription
    {
        return $this->subscriptionRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->subscriptionRepository->delete($id);
    }
}
