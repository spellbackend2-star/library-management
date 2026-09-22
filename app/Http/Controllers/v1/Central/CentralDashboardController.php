<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CentralDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $tenantStats = $this->getTenantStats();
        $subscriptionStats = $this->getSubscriptionStats();
        $paymentStats = $this->getPaymentStats();
        $recentTenants = $this->getRecentTenants();
        $recentPayments = $this->getRecentPayments();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard data retrieved successfully.',
            'data' => [
                'tenants' => $tenantStats,
                'subscriptions' => $subscriptionStats,
                'payments' => $paymentStats,
                'recent_tenants' => $recentTenants,
                'recent_payments' => $recentPayments,
            ],
        ]);
    }

    protected function getTenantStats(): array
    {
        $total = Tenant::count();
        $active = Tenant::where('status', 'active')->count();
        $inactive = Tenant::where('status', 'inactive')->count();
        $totalUsers = Tenant::whereNotNull('owner_email')->where('owner_email', '!=', '')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'total_users' => $totalUsers,
        ];
    }

    protected function getSubscriptionStats(): array
    {
        $total = Subscription::count();
        $active = Subscription::where('status', 'active')->count();
        $pending = Subscription::where('status', 'pending')->count();
        $expired = Subscription::where('status', 'expired')->count();

        return [
            'total' => $total,
            'active' => $active,
            'pending' => $pending,
            'expired' => $expired,
        ];
    }

    protected function getPaymentStats(): array
    {
        $total = SubscriptionPayment::count();
        $successful = SubscriptionPayment::where('status', 'SUCCESS')->count();
        $pending = SubscriptionPayment::where('status', 'PENDING')->count();
        $failed = SubscriptionPayment::where('status', 'FAILED')->count();

        $totalRevenue = SubscriptionPayment::where('status', 'SUCCESS')
            ->sum('amount');

        return [
            'total' => $total,
            'successful' => $successful,
            'pending' => $pending,
            'failed' => $failed,
            'total_revenue' => round($totalRevenue, 2),
        ];
    }

    protected function getRecentTenants(): array
    {
        return Tenant::latest('id')
            ->take(10)
            ->get([
                'id',
                'company_name',
                'tenant_code',
                'owner_email',
                'status',
                'created_at',
            ])
            ->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'company_name' => $tenant->company_name,
                    'tenant_code' => $tenant->tenant_code,
                    'owner_email' => $tenant->owner_email,
                    'status' => $tenant->status,
                    'created_at' => $tenant->created_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    protected function getRecentPayments(): array
    {
        return SubscriptionPayment::with(['subscription.plan', 'tenant'])
            ->latest('id')
            ->take(10)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'status' => $payment->status,
                    'transaction_id' => $payment->transaction_id,
                    'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
                    'created_at' => $payment->created_at?->format('Y-m-d H:i:s'),
                    'subscription' => $payment->subscription ? [
                        'id' => $payment->subscription->id,
                        'plan_name' => $payment->subscription->plan?->name,
                        'status' => $payment->subscription->status,
                    ] : null,
                    'tenant' => $payment->tenant ? [
                        'id' => $payment->tenant->id,
                        'company_name' => $payment->tenant->company_name,
                        'tenant_code' => $payment->tenant->tenant_code,
                    ] : null,
                ];
            })
            ->toArray();
    }
}