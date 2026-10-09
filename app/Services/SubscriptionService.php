<?php

namespace App\Services;

use App\Models\Subscription;
use App\Repositories\Interface\SubscriptionInterface;
use App\Services\Central\CentralSubscriptionPlanChangeService;

class SubscriptionService
{
    public function __construct(
        protected SubscriptionInterface $subscriptionRepository,
        protected CentralSubscriptionPlanChangeService $planChangeService
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

    public function updateStatus(int $id, string $status): Subscription
    {
        return $this->subscriptionRepository->updateStatus($id, $status);
    }

    public function cancel(int $id): Subscription
    {
        return $this->subscriptionRepository->cancel($id);
    }

    public function changePlan(int $id, int $planId): Subscription
    {
        return $this->planChangeService->changePlan($id, $planId);
    }

    public function previewPlanChange(int $id, int $planId): array
    {
        return $this->planChangeService->preview($id, $planId);
    }

    public function delete(int $id): bool
    {
        return $this->subscriptionRepository->delete($id);
    }
}
