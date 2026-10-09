<?php

namespace App\Repositories\Interface;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CentralSubscriptionPaymentInterface
{
    public function getPaginated(array $filters): LengthAwarePaginator;

    public function getSummary(array $filters): array;
}
