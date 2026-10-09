<?php

namespace App\Repositories\Eloquent;

use App\Models\SubscriptionPayment;
use App\Repositories\Interface\CentralSubscriptionPaymentInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CentralSubscriptionPaymentRepository implements CentralSubscriptionPaymentInterface
{
    public function getPaginated(array $filters): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->with([
                'subscription.plan',
                'tenant',
                'invoice',
            ])
            ->latest('id')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function getSummary(array $filters): array
    {
        unset($filters['status']);
        $payments = $this->filteredQuery($filters);

        $totalCollected = (string) $payments
            ->whereIn('status', ['SUCCESS', 'COMPLETED'])
            ->sum('amount');

        $successCount = $payments
            ->whereIn('status', ['SUCCESS', 'COMPLETED'])
            ->count();
        $pendingCount = $payments
            ->where('status', 'PENDING')
            ->count();
        $failedCount = $payments
            ->where('status', 'FAILED')
            ->count();

        return [
            'total_collected' => $totalCollected,
            'success_count' => $successCount,
            'pending_count' => $pendingCount,
            'failed_count' => $failedCount,
        ];
    }

    private function filteredQuery(array $filters): Builder
    {
        $query = SubscriptionPayment::query();

        foreach (['tenant_id', 'subscription_id', 'status', 'payment_method'] as $filter) {
            if (! empty($filters[$filter])) {
                $query->where($filter, $filters[$filter]);
            }
        }

        return $query;
    }
}
