<?php

namespace App\Services\Central;

use App\Repositories\Interface\CentralSubscriptionPaymentInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CentralSubscriptionPaymentListingService
{
    public function __construct(
        protected CentralSubscriptionPaymentInterface $subscriptionPaymentRepository
    ) {}

    public function getPaginated(array $filters): LengthAwarePaginator
    {
        return $this->subscriptionPaymentRepository->getPaginated($filters);
    }

    public function getSummary(array $filters): array
    {
        return $this->subscriptionPaymentRepository->getSummary($filters);
    }
}
