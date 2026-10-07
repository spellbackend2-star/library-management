<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\CentralTenantUpdateRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Repositories\Eloquent\CentralInvoiceRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CentralTenantController extends Controller
{
    public function __construct(
        protected CentralInvoiceRepository $centralInvoiceRepository
    ) {}

    /**
     * Get all tenants with pagination.
     */
    public function index(): JsonResponse
    {
        $tenants = Tenant::with('domains', 'currentSubscription.plan')
            ->latest('id')
            ->paginate(request()->get('per_page', 15));

        $tenants->getCollection()->transform(function ($tenant) {
            return [
                'id' => $tenant->id,
                'company_name' => $tenant->company_name,
                'phone' => $tenant->phone,
                'tenant_code' => $tenant->tenant_code,
                'owner_email' => $tenant->owner_email,
                'owner_name' => $tenant->owner_name,
                'status' => $tenant->status,
                'suspension_reason' => $tenant->suspension_reason,
                'domain' => $tenant->domains->first()?->domain,
                'subscription' => $tenant->currentSubscription
                    ? [
                        'id' => $tenant->currentSubscription->id,
                        'status' => $tenant->currentSubscription->status,
                        'starts_at' => $tenant->currentSubscription->starts_at,
                        'expires_at' => $tenant->currentSubscription->expires_at,
                        'plan' => $tenant->currentSubscription->plan
                            ? [
                                'id' => $tenant->currentSubscription->plan->id,
                                'name' => $tenant->currentSubscription->plan->name,
                                'price' => $tenant->currentSubscription->plan->price,
                                'duration' => $tenant->currentSubscription->plan->duration,
                                'duration_unit' => $tenant->currentSubscription->plan->duration_unit,
                            ]
                            : null,
                    ]
                    : null,
                'created_at' => $tenant->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Tenants retrieved successfully.',
            'data' => $tenants,
        ]);
    }

    /**
     * Get tenant detail with profile, current subscription, payments, and invoices.
     */
    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load('domains');

        // Current active subscription
        $subscription = Subscription::with('plan')
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'pending', 'expired', 'cancelled'])
            ->latest('id')
            ->first();

        // All payments for this tenant
        $payments = SubscriptionPayment::with([
            'subscription.plan',
            'invoice',
        ])
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->get();

        // All invoices for this tenant
        $invoices = $this->centralInvoiceRepository->getAll([
            'tenant_id' => $tenant->id,
            'per_page' => request()->get('per_page', 15),
            'sort_by' => 'id',
            'sort_order' => 'desc',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tenant detail retrieved successfully.',
            'data' => [
                'profile' => [
                    'id' => $tenant->id,
                    'company_name' => $tenant->company_name,
                    'phone' => $tenant->phone,
                    'tenant_code' => $tenant->tenant_code,
                    'owner_email' => $tenant->owner_email,
                    'owner_name' => $tenant->owner_name,
                    'status' => $tenant->status,
                    'suspension_reason' => $tenant->suspension_reason,
                    'domain' => $tenant->domains->first()?->domain,
                    'created_at' => $tenant->created_at,
                ],
                'subscription' => $subscription
                    ? [
                        'id' => $subscription->id,
                        'status' => $subscription->status,
                        'starts_at' => $subscription->starts_at,
                        'expires_at' => $subscription->expires_at,
                        'plan' => $subscription->plan
                            ? [
                                'id' => $subscription->plan->id,
                                'name' => $subscription->plan->name,
                                'price' => $subscription->plan->price,
                                'duration' => $subscription->plan->duration,
                                'duration_unit' => $subscription->plan->duration_unit,
                            ]
                            : null,
                    ]
                    : null,
                'payments' => $payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'status' => $payment->status,
                        'transaction_id' => $payment->transaction_id,
                        'paid_at' => $payment->paid_at,
                        'subscription' => $payment->subscription
                            ? [
                                'id' => $payment->subscription->id,
                                'status' => $payment->subscription->status,
                                'plan' => $payment->subscription->plan
                                    ? [
                                        'id' => $payment->subscription->plan->id,
                                        'name' => $payment->subscription->plan->name,
                                        'price' => $payment->subscription->plan->price,
                                    ]
                                    : null,
                            ]
                            : null,
                        'invoice' => $payment->invoice
                            ? [
                                'id' => $payment->invoice->id,
                                'invoice_number' => $payment->invoice->invoice_number,
                                'total_amount' => $payment->invoice->total_amount,
                                'paid_amount' => $payment->invoice->paid_amount,
                                'remaining_amount' => $payment->invoice->remaining_amount,
                                'status' => $payment->invoice->status,
                                'due_date' => $payment->invoice->due_date,
                            ]
                            : null,
                    ];
                }),
                'invoices' => [
                    'data' => collect($invoices['data'])->map(function ($invoice) {
                        return [
                            'id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'invoice_type' => $invoice->invoice_type,
                            'subtotal' => $invoice->subtotal,
                            'tax' => $invoice->tax,
                            'discount' => $invoice->discount,
                            'total_amount' => $invoice->total_amount,
                            'paid_amount' => $invoice->paid_amount,
                            'remaining_amount' => $invoice->remaining_amount,
                            'currency' => $invoice->currency,
                            'currency_symbol' => $invoice->currency_symbol,
                            'status' => $invoice->status,
                            'due_date' => $invoice->due_date,
                            'notes' => $invoice->notes,
                            'subscription' => $invoice->subscription
                                ? [
                                    'id' => $invoice->subscription->id,
                                    'status' => $invoice->subscription->status,
                                    'plan' => $invoice->subscription->plan
                                        ? [
                                            'id' => $invoice->subscription->plan->id,
                                            'name' => $invoice->subscription->plan->name,
                                            'price' => $invoice->subscription->plan->price,
                                        ]
                                        : null,
                                ]
                                : null,
                            'created_at' => $invoice->created_at,
                        ];
                    }),
                    'pagination' => $invoices['meta'],
                ],
            ],
        ]);
    }

    /**
     * Update tenant company information.
     */
    public function update(CentralTenantUpdateRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

        $tenant->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Tenant updated successfully.',
            'data' => [
                'id' => $tenant->id,
                'company_name' => $tenant->company_name,
                'phone' => $tenant->phone,
                'tenant_code' => $tenant->tenant_code,
                'owner_email' => $tenant->owner_email,
                'owner_name' => $tenant->owner_name,
                'status' => $tenant->status,
                'suspension_reason' => $tenant->suspension_reason,
                'domain' => $tenant->domains->first()?->domain,
                'created_at' => $tenant->created_at,
            ],
        ]);
    }

    /**
     * Update tenant status.
     */
    public function updateStatus(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        if ($tenant->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Review this tenant using the tenant review endpoint.',
            ], 422);
        }

        $tenant->update([
            'status' => $data['status'],
            'suspension_reason' => $data['status'] === 'suspended'
                ? 'ADMIN'
                : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tenant status updated successfully.',
            'data' => [
                'id' => $tenant->id,
                'company_name' => $tenant->company_name,
                'phone' => $tenant->phone,
                'tenant_code' => $tenant->tenant_code,
                'status' => $tenant->status,
                'suspension_reason' => $tenant->suspension_reason,
            ],
        ]);
    }

    /**
     * Approve or reject a tenant waiting for central admin review.
     */
    public function review(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
        ]);

        $tenant = DB::transaction(function () use ($tenant, $data): Tenant {
            $tenant = Tenant::query()
                ->lockForUpdate()
                ->findOrFail($tenant->id);

            if ($tenant->status !== 'pending') {
                throw ValidationException::withMessages([
                    'tenant' => ['Only tenants with pending status can be reviewed.'],
                ]);
            }

            $tenant->update([
                'status' => $data['decision'] === 'approve'
                    ? 'active'
                    : 'rejected',
                'suspension_reason' => null,
            ]);

            return $tenant;
        });

        return response()->json([
            'success' => true,
            'message' => $data['decision'] === 'approve'
                ? 'Tenant approved and activated successfully.'
                : 'Tenant rejected successfully.',
            'data' => [
                'id' => $tenant->id,
                'company_name' => $tenant->company_name,
                'phone' => $tenant->phone,
                'tenant_code' => $tenant->tenant_code,
                'status' => $tenant->status,
                'domain' => $tenant->domains()->first()?->domain,
            ],
        ]);
    }
}
