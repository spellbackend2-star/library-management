<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\CompletePaymentAndCreateTenantRequest;
use App\Http\Resources\v1\Central\CentralPaymentTenantCompletionResource;
use App\Http\Resources\v1\Central\CentralPlanPaymentInitiationResource;
use App\Models\CentralInvoice;
use App\Models\Coupon;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\Central\CentralCouponService;
use App\Services\Central\CentralInvoiceService;
use App\Services\Central\CentralPaymentService;
use App\Services\Central\CentralTenantService;
use App\Services\Central\CentralSubscriptionService;
use App\Services\Payments\EsewaService;
use App\Services\Payments\KhaltiService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CentralSubscriptionPaymentController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected CentralInvoiceService $centralInvoiceService,
        protected CentralPaymentService $centralPaymentService,
        protected CentralTenantService $centralTenantService,
        protected CentralSubscriptionService $centralSubscriptionService,
        protected CentralCouponService $centralCouponService,
        protected KhaltiService $khaltiService,
        protected EsewaService $esewaService
    ) {}

    /**
     * Get all subscription payments.
     */
    public function index(Request $request): JsonResponse
    {
        $this->normalizePaymentMethod($request);

        $validated = $request->validate([
            'tenant_id' => ['nullable', 'string'],
            'subscription_id' => ['nullable', 'integer'],
            'status' => [
                'nullable',
                'string',
                'in:PENDING,SUCCESS,FAILED',
            ],
            'payment_method' => [
                'nullable',
                'string',
                'in:CASH,KHALTI,ESEWA',
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = SubscriptionPayment::query();

        if (! empty($validated['tenant_id'])) {
            $query->where('tenant_id', $validated['tenant_id']);
        }

        if (! empty($validated['subscription_id'])) {
            $query->where('subscription_id', $validated['subscription_id']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['payment_method'])) {
            $query->where('payment_method', $validated['payment_method']);
        }

        $perPage = $validated['per_page'] ?? 20;

        $payments = $query
            ->with([
                'subscription.plan',
                'tenant',
                'invoice',
            ])
            ->latest('id')
            ->paginate($perPage);

        $data = $payments->getCollection()->map(function ($payment) {
            $tenant = $payment->tenant;
            $subscription = $payment->subscription;
            $plan = $subscription?->plan;
            $invoice = $payment->invoice;

            return [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'amount' => $payment->amount,
                'currency' => $invoice?->currency ?? 'NPR',
                'payment_method' => $payment->payment_method,
                'status' => $payment->status,
                'paid_at' => $payment->paid_at
                    ? \Carbon\Carbon::parse($payment->paid_at)->format('Y-m-d\TH:i:s\Z')
                    : null,
                'created_at' => $payment->created_at
                    ? \Carbon\Carbon::parse($payment->created_at)->format('Y-m-d\TH:i:s\Z')
                    : null,
                'tenant' => $tenant
                    ? [
                        'id' => $tenant->id,
                        'company_name' => $tenant->company_name,
                        'phone' => $tenant->phone,
                        'tenant_code' => $tenant->tenant_code,
                    ]
                    : null,
                'plan' => $plan
                    ? [
                        'id' => $plan->id,
                        'name' => $plan->name,
                    ]
                    : null,
                'subscription' => $subscription
                    ? [
                        'id' => $subscription->id,
                        'status' => $subscription->status,
                        'starts_at' => $subscription->starts_at ? (string) $subscription->starts_at : null,
                        'expires_at' => $subscription->expires_at ? (string) $subscription->expires_at : null,
                    ]
                    : null,
                'invoice' => $invoice
                    ? [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'status' => $invoice->status,
                    ]
                    : null,
            ];
        });

        // Summary stats
        $allPayments = SubscriptionPayment::query();
        if (! empty($validated['tenant_id'])) {
            $allPayments->where('tenant_id', $validated['tenant_id']);
        }
        if (! empty($validated['subscription_id'])) {
            $allPayments->where('subscription_id', $validated['subscription_id']);
        }
        if (! empty($validated['payment_method'])) {
            $allPayments->where('payment_method', $validated['payment_method']);
        }

        $totalCollected = (string) $allPayments
            ->whereIn('status', ['SUCCESS', 'COMPLETED'])
            ->sum('amount');

        $successCount = $allPayments->whereIn('status', ['SUCCESS', 'COMPLETED'])->count();
        $pendingCount = $allPayments->where('status', 'PENDING')->count();
        $failedCount = $allPayments->where('status', 'FAILED')->count();

        return response()->json([
            'success' => true,
            'message' => 'Subscription payments retrieved successfully.',
            'data' => $data,
            'meta' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
            ],
            'summary' => [
                'total_collected' => $totalCollected,
                'success_count' => $successCount,
                'pending_count' => $pendingCount,
                'failed_count' => $failedCount,
            ],
        ]);
    }

    /**
     * Create invoice and subscription payment.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->normalizePaymentMethod($request);

        $data = $request->validate([
            'subscription_id' => [
                'required',
                'integer',
                'exists:subscriptions,id',
            ],

            'payment_method' => [
                'required',
                'string',
                'regex:/^(CASH|KHALTI|ESEWA)$/i',
            ],

            'amount' => [
                'nullable',
                'numeric',
                'gt:0',
            ],

            'return_url' => [
                'required',
                'url',
            ],

            'coupon_id' => [
                'nullable',
                'integer',
                'exists:coupons,id',
            ],
        ]);

        $returnUrl = $data['return_url'] ?? null;

        $subscription = Subscription::with('plan')
            ->findOrFail($data['subscription_id']);

        if (! $subscription->plan) {
            return $this->errorResponse(
                'Subscription plan not found.',
                422
            );
        }

        if (
            ! $subscription->plan->price ||
            (float) $subscription->plan->price <= 0
        ) {
            return $this->errorResponse(
                'Subscription plan price must be greater than 0.',
                422
            );
        }

        $pricingPlan = (float) $subscription->plan->price;

        // Apply coupon if provided
        $couponData = $this->centralCouponService->calculatePaymentCoupon(
            isset($data['coupon_id']) ? (int) $data['coupon_id'] : null,
            $pricingPlan
        );

        if ($couponData['error'] !== null) {
            return $this->errorResponse($couponData['error'], 422);
        }

        $couponDiscount = $couponData['coupon_discount'];
        $couponId = $couponData['coupon_id'];

        $totalAmount = round($pricingPlan - $couponDiscount, 2);

        $paymentAmount = round(
            (float) ($data['amount'] ?? $totalAmount),
            2
        );

        if ($paymentAmount > $totalAmount) {
            return $this->errorResponse(
                'Payment amount cannot be greater than the invoice remaining amount.',
                422
            );
        }
        /*
                    'remaining_amount' => $totalAmount,
                ],
                'errors' => [
                    'amount' => [
                        'The payment amount cannot exceed the invoice remaining amount.',
                    ],
                ],
            ], 422);
        }
        /*
         * Create invoice.
         */
        $invoice = $this->centralInvoiceService->create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'invoice_number' =>
            CentralInvoice::generateInvoiceNumber(),
            'invoice_type' => 'subscription',
            'subtotal' => $pricingPlan,
            'tax' => 0,
            'discount' => 0,
            'coupon_id' => $couponId,
            'coupon_discount' => $couponDiscount,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'remaining_amount' => $totalAmount,
            'status' => 'unpaid',
            'due_date' => now()
                ->addDays(7)
                ->toDateString(),
        ]);

        /*
         * Create payment.
         */
        $payment = SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'invoice_id' => $invoice->id,
            'tenant_id' => $subscription->tenant_id,
            'amount' => $paymentAmount,
            'payment_method' =>
            strtoupper($data['payment_method']),
            'status' => 'PENDING',
        ]);

        $invoice->update([
            'subscription_payment_id' => $payment->id,
        ]);

        // Increment coupon usage count if coupon was applied
        if ($couponId) {
            $this->centralCouponService->recordUsage($couponId);
        }

        /*
         * CASH.
         */
        if (
            strtoupper($data['payment_method']) === 'CASH'
        ) {
            $payment = $this->centralPaymentService
                ->completeCentralCashPayment($payment);

            if ($returnUrl) {
                return $this->redirectPaymentFrontend(
                    $payment,
                    $returnUrl,
                    'Payment completed successfully.'
                );
            }

            return $this->successResponse(
                $payment->fresh()->load([
                    'subscription.plan',
                    'tenant',
                    'invoice',
                ]),
                'Cash payment completed successfully.',
                201
            );
        }

        /*
         * KHALTI.
         */
        if (
            strtoupper($data['payment_method']) === 'KHALTI'
        ) {
            try {
                $result =
                    $this->khaltiService
                    ->initiateSubscription(
                        $payment,
                        $returnUrl
                    );

                return response()->json([
                    'success' => true,
                    'message' =>
                    'Invoice created and Khalti payment initiated.',
                    'data' => [
                        'subscription' =>
                        $subscription->load('plan'),

                        'invoice' =>
                        $invoice->load(
                            'subscription.plan'
                        ),

                        'subscription_payment' =>
                        $this->paymentWithReturnUrl(
                            $payment->fresh()->load([
                                'subscription.plan',
                                'invoice',
                            ]),
                            $returnUrl
                        ),

                        'khalti' => $result,
                    ],
                ], 201);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' =>
                    'Khalti initiation failed: ' .
                        $e->getMessage(),
                ], 500);
            }
        }

        /*
         * ESEWA.
         */
        if (
            strtoupper($data['payment_method']) === 'ESEWA'
        ) {
            try {
                $result =
                    $this->esewaService
                    ->initiateSubscription(
                        $payment,
                        $returnUrl
                    );

                return response()->json([
                    'success' => true,
                    'message' =>
                    'Invoice created and eSewa payment initiated.',
                    'data' => [
                        'subscription' =>
                        $subscription->load('plan'),

                        'invoice' =>
                        $invoice->load(
                            'subscription.plan'
                        ),

                        'subscription_payment' =>
                        $this->paymentWithReturnUrl(
                            $payment->fresh()->load([
                                'subscription.plan',
                                'invoice',
                            ]),
                            $returnUrl
                        ),

                        'esewa' => $result,
                    ],
                ], 201);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' =>
                    'eSewa initiation failed: ' .
                        $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' =>
            'Invoice and subscription payment created successfully.',
            'data' => $payment
                ->fresh()
                ->load([
                    'subscription.plan',
                    'tenant',
                    'invoice',
                ]),
        ], 201);
    }

    /**
     * Create subscription from plan and initiate payment.
     */
    public function initiateFromPlan(
        Request $request
    ): JsonResponse|RedirectResponse {
        $this->normalizePaymentMethod($request);

        $data = $request->validate([
            'subscription_plan_id' => [
                'required',
                'integer',
                'exists:subscription_plans,id',
            ],

            'payment_method' => [
                'required',
                'string',
                'in:CASH,KHALTI,ESEWA',
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'return_url' => [
                'nullable',
                'url',
            ],

            'coupon_id' => [
                'nullable',
                'integer',
                'exists:coupons,id',
            ],
        ]);

        $returnUrl = $data['return_url'] ?? null;

        $plan = SubscriptionPlan::where(
            'id',
            $data['subscription_plan_id']
        )
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            return response()->json([
                'success' => false,
                'message' =>
                'Selected subscription plan is not available.',
            ], 422);
        }

        if (
            ! $plan->price ||
            (float) $plan->price <= 0
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                'Subscription plan price must be greater than 0.',
            ], 422);
        }

        $pricingPlan = (float) $plan->price;

        // Apply coupon if provided
        $couponData = $this->centralCouponService->calculatePaymentCoupon(
            isset($data['coupon_id']) ? (int) $data['coupon_id'] : null,
            $pricingPlan
        );

        if ($couponData['error'] !== null) {
            return response()->json([
                'success' => false,
                'message' => $couponData['error'],
            ], 422);
        }

        $couponDiscount = $couponData['coupon_discount'];
        $couponId = $couponData['coupon_id'];

        $totalAmount = round($pricingPlan - $couponDiscount, 2);

        $paymentAmount = round((float) $data['amount'], 2);

        if ($paymentAmount > $totalAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount cannot be greater than the invoice remaining amount.',
                'data' => [
                    'invoice_total' => $totalAmount,
                    'requested_amount' => $paymentAmount,
                    'remaining_amount' => $totalAmount,
                ],
            ], 422);
        }

        /*
         * Create subscription.
         */
        $subscription = $this->centralSubscriptionService->createPendingSubscription(null, $plan);

        /*
         * Create invoice.
         */
        $invoice = $this->centralInvoiceService->create([
            'tenant_id' => null,
            'subscription_id' => $subscription->id,
            'invoice_number' =>
            CentralInvoice::generateInvoiceNumber(),
            'invoice_type' => 'subscription',
            'subtotal' => $pricingPlan,
            'tax' => 0,
            'discount' => 0,
            'coupon_id' => $couponId,
            'coupon_discount' => $couponDiscount,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'remaining_amount' => $totalAmount,
            'status' => 'unpaid',
            'due_date' => now()
                ->addDays(7)
                ->toDateString(),
        ]);

        /*
         * Create payment.
         */
        $payment = SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'invoice_id' => $invoice->id,
            'tenant_id' => null,
            'amount' => $paymentAmount,
            'payment_method' =>
            strtoupper($data['payment_method']),
            'status' => 'PENDING',
        ]);

        $invoice->update([
            'subscription_payment_id' => $payment->id,
        ]);

        // Increment coupon usage count if coupon was applied
        if ($couponId) {
            $this->centralCouponService->recordUsage($couponId);
        }

        /*
         * KHALTI.
         */
        if (
            strtoupper($data['payment_method']) === 'KHALTI'
        ) {
            try {
                $result =
                    $this->khaltiService
                    ->initiateSubscription(
                        $payment,
                        $returnUrl
                    );

                return $this->planPaymentInitiationResponse(
                    $subscription,
                    $invoice,
                    $payment,
                    'Invoice created and Khalti payment initiated.',
                    $result
                );
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' =>
                    'Khalti initiation failed: ' .
                        $e->getMessage(),
                ], 500);
            }
        }

        /*
         * ESEWA.
         */
        if (
            strtoupper($data['payment_method']) === 'ESEWA'
        ) {
            try {
                $result =
                    $this->esewaService
                    ->initiateSubscription(
                        $payment,
                        $returnUrl
                    );

                return $this->planPaymentInitiationResponse(
                    $subscription,
                    $invoice,
                    $payment,
                    'Invoice created and eSewa payment initiated.',
                    $result
                );
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' =>
                    'eSewa initiation failed: ' .
                        $e->getMessage(),
                ], 500);
            }
        }

        /*
         * CASH requires central admin confirmation before registration.
         */
        if (
            strtoupper($data['payment_method']) === 'CASH'
        ) {
            return $this->planPaymentInitiationResponse(
                $subscription->fresh()->load('plan'),
                $invoice->fresh(),
                $payment,
                'Cash payment is awaiting central admin confirmation.'
            );
        }

        return $this->planPaymentInitiationResponse(
            $subscription,
            $invoice,
            $payment,
            'Invoice and subscription payment created successfully. Proceed to payment.'
        );
    }

    private function planPaymentInitiationResponse(
        Subscription $subscription,
        CentralInvoice $invoice,
        SubscriptionPayment $payment,
        string $message,
        ?array $gateway = null
    ): JsonResponse {
        $resourceData = [
            'subscription' => $subscription->fresh()->load('plan'),
            'invoice' => $invoice->fresh(),
            'payment' => $payment->fresh(),
        ];

        if ($gateway !== null) {
            $resourceData['gateway'] = $gateway;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => (new CentralPlanPaymentInitiationResource($resourceData))
                ->resolve(request()),
        ], 201);
    }

    /**
     * Complete payment and create tenant.
     */
    public function completeAndCreateTenant(
        CompletePaymentAndCreateTenantRequest $request,
        SubscriptionPayment $payment
    ): JsonResponse {
        $data = $request->validated();

        $hasSuccessfulPayment = in_array(
            $payment->status,
            ['SUCCESS', 'COMPLETED'],
            true
        );
        $hasPartialInvoicePayment = (float) $payment->invoice?->paid_amount > 0;

        if (! $hasSuccessfulPayment && ! $hasPartialInvoicePayment) {
            return response()->json([
                'success' => false,
                'message' =>
                'A successful payment or partial invoice payment is required before creating a tenant.',
            ], 422);
        }

        $result =
            $this->centralTenantService
            ->completePaymentAndCreateTenant(
                $payment,
                $data
            );

        return response()->json([
            'success' => true,
            'message' =>
            'Payment verified and tenant registered successfully. The tenant is pending admin review.',
            'data' => (new CentralPaymentTenantCompletionResource($result))
                ->resolve($request),
        ], 201);
    }

    /**
     * Verify Khalti payment.
     */
    public function verifyKhalti(
        Request $request,
        SubscriptionPayment $payment
    ) {
        /*
         * return_url was originally supplied by frontend.
         * Khalti sends it back to this endpoint through the
         * backend verification URL.
         */
        $returnUrl = $request->query('return_url');

        if ($payment->payment_method !== 'KHALTI') {
            return response()->json([
                'success' => false,
                'message' =>
                'Invalid payment method for Khalti.',
            ], 400);
        }

        try {
            /*
             * Prevent duplicate completion.
             */
            if (in_array(
                $payment->status,
                ['SUCCESS', 'COMPLETED'],
                true
            )) {
                return $this->redirectKhaltiFrontend(
                    $payment,
                    'success',
                    'Payment already completed successfully.',
                    $returnUrl
                );
            }

            /*
             * Transaction ID is required.
             */
            if (! $payment->transaction_id) {
                throw new \Exception(
                    'Khalti transaction ID not found.'
                );
            }

            /*
             * Ask Khalti for actual payment status.
             */
            $result = $this->khaltiService
                ->verifySubscriptionPayment($payment);

            $gatewayStatus = strtoupper(
                (string) ($result['status'] ?? '')
            );

            /*
             * Payment successful.
             */
            if (in_array(
                $gatewayStatus,
                ['COMPLETED', 'SUCCESS'],
                true
            )) {
                $payment->update([
                    'gateway_response' =>
                    $result['gateway_response'] ?? null,

                    'transaction_id' =>
                    $result['transaction_id']
                        ?? $payment->transaction_id,
                ]);

                Log::info(
                    'CENTRAL KHALTI PAYMENT COMPLETING',
                    [
                        'payment_id' => $payment->id,
                        'subscription_id' =>
                        $payment->subscription_id,
                        'invoice_id' =>
                        $payment->invoice_id,
                        'amount' => $payment->amount,
                        'gateway_status' =>
                        $gatewayStatus,
                    ]
                );

                /*
                 * Apply this installment to the invoice and activate the
                 * subscription after any successful payment.
                 */
                $this->centralPaymentService
                    ->completeCentralCashPayment(
                        $payment
                    );

                /*
                 * Refresh after completion.
                 */
                $payment->refresh();

                $payment->load([
                    'subscription.plan',
                    'tenant.domains',
                    'invoice',
                ]);

                Log::info(
                    'CENTRAL KHALTI PAYMENT COMPLETED',
                    [
                        'payment_id' => $payment->id,
                        'subscription_id' =>
                        $payment->subscription_id,
                        'invoice_id' =>
                        $payment->invoice_id,
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'invoice_status' =>
                        $payment->invoice?->status,
                        'paid_amount' =>
                        $payment->invoice?->paid_amount,
                        'remaining_amount' =>
                        $payment->invoice?->remaining_amount,
                        'subscription_status' =>
                        $payment->subscription?->status,
                        'tenant_status' =>
                        $payment->tenant?->status,
                    ]
                );

                /*
                 * Redirect to frontend.
                 */
                return $this->redirectKhaltiFrontend(
                    $payment,
                    'success',
                    'Payment completed successfully.',
                    $returnUrl
                );
            }

            /*
             * Payment still pending.
             */
            if ($gatewayStatus === 'PENDING') {
                $payment->update([
                    'status' => 'PENDING',

                    'gateway_response' =>
                    $result['gateway_response'] ?? null,
                ]);

                $payment->refresh();

                Log::info(
                    'CENTRAL KHALTI PAYMENT PENDING',
                    [
                        'payment_id' => $payment->id,
                        'invoice_id' =>
                        $payment->invoice_id,
                    ]
                );

                return $this->redirectKhaltiFrontend(
                    $payment,
                    'pending',
                    'Payment is still pending.',
                    $returnUrl
                );
            }

            /*
             * Payment failed/cancelled.
             */
            $payment->update([
                'status' => 'FAILED',

                'gateway_response' =>
                $result['gateway_response'] ?? null,
            ]);

            $payment->refresh();

            Log::warning(
                'CENTRAL KHALTI PAYMENT FAILED',
                [
                    'payment_id' => $payment->id,
                    'invoice_id' =>
                    $payment->invoice_id,
                    'gateway_status' =>
                    $gatewayStatus,
                ]
            );

            return $this->redirectKhaltiFrontend(
                $payment,
                'failed',
                'Payment failed or was cancelled.',
                $returnUrl
            );
        } catch (Throwable $e) {
            Log::error(
                'CENTRAL KHALTI PAYMENT VERIFICATION ERROR',
                [
                    'payment_id' => $payment->id,
                    'subscription_id' =>
                    $payment->subscription_id,
                    'invoice_id' =>
                    $payment->invoice_id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->redirectKhaltiFrontend(
                $payment,
                'error',
                'Unable to verify payment.',
                $returnUrl
            );
        }
    }

    /**
     * Verify eSewa payment.
     */
    public function verifyEsewa(
        Request $request,
        SubscriptionPayment $payment
    ) {
        $returnUrl = $request->query('return_url');

        if ($payment->payment_method !== 'ESEWA') {
            return response()->json([
                'success' => false,
                'message' =>
                'Invalid payment method for eSewa.',
            ], 400);
        }

        try {
            /*
             * Prevent duplicate completion.
             */
            if (in_array(
                $payment->status,
                ['SUCCESS', 'COMPLETED'],
                true
            )) {
                return $this->redirectEsewaFrontend(
                    $payment,
                    'success',
                    'Payment already completed successfully.',
                    $returnUrl
                );
            }

            /*
             * Verify eSewa payment.
             */
            $result =
                $this->esewaService->verify(
                    $request->all()
                );

            $gatewayStatus = strtoupper(
                (string) ($result['status'] ?? '')
            );

            /*
             * Payment successful.
             */
            if (in_array(
                $gatewayStatus,
                ['COMPLETED', 'SUCCESS'],
                true
            )) {
                $payment->update([
                    'gateway_response' =>
                    $result['gateway_response'] ?? null,

                    'transaction_id' =>
                    $result['transaction_id']
                        ?? $payment->transaction_id,
                ]);

                Log::info(
                    'CENTRAL ESEWA PAYMENT COMPLETING',
                    [
                        'payment_id' => $payment->id,
                        'subscription_id' =>
                        $payment->subscription_id,
                        'invoice_id' =>
                        $payment->invoice_id,
                        'amount' => $payment->amount,
                        'gateway_status' =>
                        $gatewayStatus,
                    ]
                );

                /*
                 * Complete payment.
                 */
                $this->centralPaymentService
                    ->completeCentralCashPayment(
                        $payment
                    );

                $payment->refresh();

                $payment->load([
                    'subscription.plan',
                    'tenant.domains',
                    'invoice',
                ]);

                return $this->redirectEsewaFrontend(
                    $payment,
                    'success',
                    'Payment completed successfully.',
                    $returnUrl
                );
            }

            /*
             * Pending.
             */
            if ($gatewayStatus === 'PENDING') {
                $payment->update([
                    'status' => 'PENDING',

                    'gateway_response' =>
                    $result['gateway_response'] ?? null,
                ]);

                $payment->refresh();

                return $this->redirectEsewaFrontend(
                    $payment,
                    'pending',
                    'Payment is still pending.',
                    $returnUrl
                );
            }

            /*
             * Failed/cancelled.
             */
            $payment->update([
                'status' => 'FAILED',

                'gateway_response' =>
                $result['gateway_response'] ?? null,
            ]);

            $payment->refresh();

            return $this->redirectEsewaFrontend(
                $payment,
                'failed',
                'Payment failed or was cancelled.',
                $returnUrl
            );
        } catch (Throwable $e) {
            Log::error(
                'CENTRAL ESEWA PAYMENT VERIFICATION ERROR',
                [
                    'payment_id' => $payment->id,
                    'subscription_id' =>
                    $payment->subscription_id,
                    'invoice_id' =>
                    $payment->invoice_id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->redirectEsewaFrontend(
                $payment,
                'error',
                'Unable to verify payment.',
                $returnUrl
            );
        }
    }

    /**
     * Show payment.
     */
    public function show(
        SubscriptionPayment $payment
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $payment->load([
                'subscription.plan',
                'tenant',
                'invoice',
            ]),
        ]);
    }

    /**
     * Complete payment manually.
     */
    public function complete(
        SubscriptionPayment $payment
    ): JsonResponse {
        if ($payment->payment_method !== 'CASH') {
            return response()->json([
                'success' => false,
                'message' => 'Only cash payments can be confirmed by an admin.',
            ], 422);
        }

        if ($payment->status !== 'PENDING') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending cash payments can be confirmed.',
            ], 422);
        }

        $this->centralPaymentService
            ->completeCentralCashPayment(
                $payment
            );

        return response()->json([
            'success' => true,
            'message' =>
            'Payment completed successfully.',
            'data' => $payment
                ->fresh()
                ->load([
                    'subscription.plan',
                    'tenant',
                    'invoice',
                ]),
        ]);
    }

    /**
     * Initiate an additional payment against a central invoice balance.
     */
    public function payInvoice(Request $request): JsonResponse
    {
        $this->normalizePaymentMethod($request);

        $data = $request->validate([
            'invoice_id' => [
                'required',
                'integer',
                'exists:invoices,id',
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'payment_method' => [
                'required',
                'string',
                Rule::in(['CASH', 'KHALTI']),
            ],
            'return_url' => [
                'nullable',
                'url',
            ],
        ]);

        // This route stays public for online Khalti checkout, but recording
        // cash changes the invoice immediately and must be done by central
        // staff who can create subscription payments.
        if ($data['payment_method'] === 'CASH') {
            $centralUser = $request->user('api');

            if (! $centralUser || ! $centralUser->can('subscription_payments.create')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to record cash payments.',
                ], 403);
            }
        }

        $invoice = CentralInvoice::query()
            ->with(['subscription.plan', 'tenant.domains'])
            ->findOrFail($data['invoice_id']);

        $subscription = $invoice->subscription;
        if (! $subscription || ! $subscription->plan) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription plan not found for this invoice.',
            ], 422);
        }

        $remainingAmount = max(
            0,
            round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2)
        );

        if ($remainingAmount <= 0 || $invoice->status === 'paid') {
            $completedPayment = $invoice->payments()
                ->whereIn('status', ['SUCCESS', 'COMPLETED'])
                ->latest('id')
                ->first();

            if ($completedPayment) {
                $completedPayment->load([
                    'subscription.plan',
                    'tenant.domains',
                    'invoice',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Invoice is already fully paid.',
                    'data' => $this->buildPaymentSummary($completedPayment),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invoice has no remaining balance, but no completed payment record was found.',
            ], 422);
        }

        $requestedAmount = round((float) $data['amount'], 2);
        if ($requestedAmount > $remainingAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount cannot be greater than the invoice remaining amount.',
                'errors' => [
                    'amount' => [
                        "The payment amount cannot exceed the remaining balance of {$remainingAmount}.",
                    ],
                ],
            ], 422);
        }

        $paymentResult = DB::transaction(function () use (
            $invoice,
            $subscription,
            $requestedAmount,
            $data
        ): array {
            $lockedInvoice = CentralInvoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            $lockedRemaining = max(
                0,
                round((float) $lockedInvoice->total_amount - (float) $lockedInvoice->paid_amount, 2)
            );

            if ($lockedRemaining <= 0 || $requestedAmount > $lockedRemaining) {
                throw ValidationException::withMessages([
                    'amount' => ['The requested amount exceeds the invoice remaining balance.'],
                ]);
            }

            $pendingPayments = SubscriptionPayment::query()
                ->where('invoice_id', $lockedInvoice->id)
                ->where('status', 'PENDING')
                ->lockForUpdate()
                ->get();

            if ($pendingPayments->isNotEmpty()) {
                $pendingPayment = $pendingPayments->count() === 1
                    ? $pendingPayments->first()
                    : null;
                $gatewayResponse = $pendingPayment?->gateway_response ?? [];
                $pendingPidx = is_array($gatewayResponse)
                    ? ($gatewayResponse['pidx'] ?? $pendingPayment?->transaction_id)
                    : $pendingPayment?->transaction_id;
                $pendingPaymentUrl = is_array($gatewayResponse)
                    ? ($gatewayResponse['payment_url'] ?? null)
                    : null;

                if (
                    $pendingPayment
                    && $data['payment_method'] === 'KHALTI'
                    && $pendingPayment->payment_method === 'KHALTI'
                    && round((float) $pendingPayment->amount, 2) === $requestedAmount
                    && $pendingPidx
                    && $pendingPaymentUrl
                ) {
                    return [
                        'payment' => $pendingPayment,
                        'gateway' => $gatewayResponse,
                        'reused_gateway' => true,
                    ];
                }

                $canReuseDraft = $pendingPayment
                    && (float) $lockedInvoice->paid_amount === 0.0
                    && $pendingPayment->payment_method === 'CASH'
                    && ! $pendingPayment->transaction_id
                    && ! $pendingPayment->gateway_response;

                if (! $canReuseDraft) {
                    throw ValidationException::withMessages([
                        'invoice_id' => [
                            'A payment attempt is already pending for this invoice. Complete or verify it before starting another payment.',
                        ],
                    ]);
                }

                $pendingPayment->update([
                    'amount' => $requestedAmount,
                    'payment_method' => $data['payment_method'],
                    'transaction_id' => null,
                    'gateway_response' => null,
                    'paid_at' => null,
                ]);
                $payment = $pendingPayment->fresh();
            } else {
                $payment = SubscriptionPayment::create([
                    'subscription_id' => $subscription->id,
                    'invoice_id' => $lockedInvoice->id,
                    'tenant_id' => $lockedInvoice->tenant_id,
                    'amount' => $requestedAmount,
                    'payment_method' => $data['payment_method'],
                    'status' => 'PENDING',
                ]);
            }

            if ($data['payment_method'] === 'CASH') {
                $payment = $this->centralPaymentService
                    ->completeCentralCashPayment($payment);
            }

            return [
                'payment' => $payment,
                'gateway' => null,
                'reused_gateway' => false,
            ];
        }, 3);

        /** @var SubscriptionPayment $payment */
        $payment = $paymentResult['payment'];

        if ($paymentResult['reused_gateway']) {
            $payment->load([
                'subscription.plan',
                'tenant.domains',
                'invoice',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'A Khalti payment is already pending. Continue with the existing payment link.',
                'data' => $this->buildPaymentSummary($payment, $paymentResult['gateway']),
            ]);
        }

        $payment->load([
            'subscription.plan',
            'tenant.domains',
            'invoice',
        ]);

        $returnUrl = $data['return_url'] ?? null;

        if ($data['payment_method'] === 'CASH') {
            $payment->refresh()->load([
                'subscription.plan',
                'tenant.domains',
                'invoice',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cash payment recorded successfully.',
                'data' => $this->buildPaymentSummary($payment),
            ], 201);
        }

        try {
            $gateway = $this->khaltiService->initiateSubscription($payment, $returnUrl);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $data['payment_method'] . ' initiation failed: ' . $e->getMessage(),
            ], 500);
        }

        $payment->refresh()->load([
            'subscription.plan',
            'tenant.domains',
            'invoice',
        ]);

        return response()->json([
            'success' => true,
            'message' => $data['payment_method'] . ' payment initiated for the invoice balance.',
            'data' => $this->buildPaymentSummary($payment, $gateway, $returnUrl),
        ], 201);
    }

    /**
     * Pay existing pending tenant subscription payment.
     */
    public function pay(
        Request $request,
        SubscriptionPayment $payment
    ): JsonResponse|RedirectResponse {
        $this->normalizePaymentMethod($request);

        $data = $request->validate([
            'payment_method' => [
                'required',
                'string',
                'in:CASH,KHALTI,ESEWA',
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'return_url' => [
                'nullable',
                'url',
            ],
        ]);

        $returnUrl = $data['return_url'] ?? null;

        $payment->load([
            'subscription.plan',
            'tenant.domains',
            'invoice',
        ]);

        if (! $payment->tenant) {
            return response()->json([
                'success' => false,
                'message' =>
                'This payment is not linked to a tenant. Use complete-and-create-tenant instead.',
            ], 422);
        }

        $invoice = $payment->invoice;
        if (! $invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found for this payment.',
            ], 422);
        }

        $method = strtoupper(
            $data['payment_method']
        );
        $requestedAmount = round((float) $data['amount'], 2);
        $remainingAmount = round(
            (float) $invoice->remaining_amount,
            2
        );

        if ($requestedAmount > $remainingAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount cannot be greater than the invoice remaining amount.',
                'errors' => [
                    'amount' => [
                        "The payment amount cannot exceed the remaining balance of {$remainingAmount}.",
                    ],
                ],
            ], 422);
        }

        /*
         * A completed payment is immutable. Start a new payment against
         * its existing invoice so partial balances retain payment history.
         */
        if (in_array($payment->status, ['SUCCESS', 'COMPLETED'], true)) {
            $completedPayment = $payment;
            $payment = SubscriptionPayment::query()
                ->where('invoice_id', $completedPayment->invoice_id)
                ->where('status', 'PENDING')
                ->latest('id')
                ->first();

            if ($payment) {
                $payment->update([
                    'amount' => $requestedAmount,
                    'payment_method' => $method,
                    'transaction_id' => null,
                    'gateway_response' => null,
                ]);
            } else {
                $payment = SubscriptionPayment::create([
                    'subscription_id' => $completedPayment->subscription_id,
                    'invoice_id' => $invoice->id,
                    'tenant_id' => $completedPayment->tenant_id,
                    'amount' => $requestedAmount,
                    'payment_method' => $method,
                    'status' => 'PENDING',
                ]);
            }

            $payment->load([
                'subscription.plan',
                'tenant.domains',
                'invoice',
            ]);
        } else {
            $attemptChanged = $payment->status === 'FAILED'
                || round((float) $payment->amount, 2) !== $requestedAmount
                || $payment->payment_method !== $method;

            $payment->update([
                'status' => 'PENDING',
                'paid_at' => null,
                'amount' => $requestedAmount,
                'payment_method' => $method,
                ...($attemptChanged ? [
                    'transaction_id' => null,
                    'gateway_response' => null,
                ] : []),
            ]);
        }

        /*
         * CASH.
         */
        if ($method === 'CASH') {
            $payment = $this->centralPaymentService
                ->completeCentralCashPayment(
                    $payment
                );

            if ($returnUrl) {
                return $this->redirectPaymentFrontend(
                    $payment,
                    $returnUrl,
                    'Payment completed successfully.'
                );
            }

            return response()->json([
                'success' => true,
                'message' => $payment->tenant?->status === 'active'
                    ? 'Cash payment recorded. Subscription activated and tenant is now active.'
                    : 'Cash payment recorded. Tenant registration is pending central admin review.',
                'data' =>
                $this->buildPaymentSummary(
                    $payment->fresh([
                        'subscription.plan',
                        'tenant.domains',
                        'invoice',
                    ]),
                    null,
                    $returnUrl
                ),
            ]);
        }

        try {
            $gateway = $method === 'KHALTI'
                ? $this->khaltiService
                ->initiateSubscription(
                    $payment,
                    $returnUrl
                )
                : $this->esewaService
                ->initiateSubscription(
                    $payment,
                    $returnUrl
                );
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' =>
                $method .
                    ' initiation failed: ' .
                    $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $payment->tenant?->status === 'active'
                ? $method . ' payment initiated. Complete the payment on the gateway to update the invoice balance.'
                : $method . ' payment initiated. Complete the payment on the gateway to activate the tenant.',
            'data' =>
            $this->buildPaymentSummary(
                $payment->fresh([
                    'subscription.plan',
                    'tenant.domains',
                    'invoice',
                ]),
                $gateway,
                $returnUrl
            ),
        ]);
    }

    /**
     * Public payment status.
     */
    public function paymentStatus(
        SubscriptionPayment $payment
    ): JsonResponse {
        $payment->load([
            'subscription.plan',
            'tenant.domains',
            'invoice',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
            'Subscription payment status retrieved successfully.',
            'data' =>
            $this->buildPaymentSummary(
                $payment
            ),
        ]);
    }

    /**
     * Build payment summary.
     */
    protected function buildPaymentSummary(
        SubscriptionPayment $payment,
        ?array $gateway = null,
        ?string $returnUrl = null
    ): array {
        $payment->loadMissing([
            'subscription.plan',
            'tenant.domains',
            'invoice',
        ]);

        $tenant = $payment->tenant;

        $isPaid = in_array(
            $payment->status,
            ['SUCCESS', 'COMPLETED'],
            true
        );

        $isTenantActive =
            $tenant &&
            $tenant->status === 'active';

        $domain =
            $tenant?->domains?->first()?->domain;

        $summary = [
            'payment' => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_method' =>
                $payment->payment_method,
                'status' => $payment->status,
                'transaction_id' =>
                $payment->transaction_id,
                'payment_url' =>
                $payment->payment_url,
                'paid_at' => $payment->paid_at,
            ],

            'invoice' => $payment->invoice
                ? [
                    'id' =>
                    $payment->invoice->id,

                    'invoice_number' =>
                    $payment->invoice->invoice_number,

                    'coupon_id' =>
                    $payment->invoice->coupon_id,

                    'coupon_discount' =>
                    $payment->invoice->coupon_discount,

                    'total_amount' =>
                    $payment->invoice->total_amount,

                    'paid_amount' =>
                    $payment->invoice->paid_amount,

                    'remaining_amount' =>
                    $payment->invoice->remaining_amount,

                    'status' =>
                    $payment->invoice->status,

                    'due_date' =>
                    $payment->invoice->due_date,
                ]
                : null,

            'payments' => $payment->invoice
                ? $payment->invoice->payments()
                ->orderBy('id')
                ->get([
                    'id',
                    'invoice_id',
                    'subscription_id',
                    'amount',
                    'payment_method',
                    'status',
                    'transaction_id',
                    'paid_at',
                ])
                ->map(fn(SubscriptionPayment $invoicePayment): array => [
                    'id' => $invoicePayment->id,
                    'invoice_id' => $invoicePayment->invoice_id,
                    'subscription_id' => $invoicePayment->subscription_id,
                    'amount' => $invoicePayment->amount,
                    'payment_method' => $invoicePayment->payment_method,
                    'status' => $invoicePayment->status,
                    'transaction_id' => $invoicePayment->transaction_id,
                    'paid_at' => $invoicePayment->paid_at,
                ])
                ->all()
                : [],

            'subscription' =>
            $payment->subscription
                ? [
                    'id' =>
                    $payment->subscription->id,

                    'status' =>
                    $payment->subscription->status,

                    'starts_at' =>
                    $payment->subscription->starts_at,

                    'expires_at' =>
                    $payment->subscription->expires_at,

                    'plan' =>
                    $payment->subscription->plan
                        ? [
                            'id' =>
                            $payment->subscription
                                ->plan->id,

                            'name' =>
                            $payment->subscription
                                ->plan->name,

                            'price' =>
                            $payment->subscription
                                ->plan->price,

                            'duration' =>
                            $payment->subscription
                                ->plan->duration,

                            'duration_unit' =>
                            $payment->subscription
                                ->plan
                                ->duration_unit,
                        ]
                        : null,
                ]
                : null,

            'tenant' => $tenant
                ? [
                    'id' => $tenant->id,

                    'company_name' =>
                    $tenant->company_name,

                    'tenant_code' =>
                    $tenant->tenant_code,

                    'status' =>
                    $tenant->status,

                    'domain' => $domain,
                ]
                : null,

            'is_paid' => $isPaid,

            'is_tenant_active' =>
            $isTenantActive,
        ];

        if ($gateway) {
            $summary['gateway'] = $gateway;
        }

        if ($returnUrl !== null) {
            $summary['payment']['return_url'] = $returnUrl;
        }

        if ($isTenantActive) {
            $summary['next_step'] =
                'Payment successful. The tenant is active and can now log in.';

            $summary['login'] = [
                'url' => $domain
                    ? 'https://' . $domain . '/login'
                    : null,

                'method' => 'POST',
            ];

            if ((float) $payment->invoice?->remaining_amount > 0) {
                $summary['pay'] = [
                    'method' => 'POST',
                    'url' => route(
                        'central.subscription-payments.pay',
                        ['payment' => $payment->id]
                    ),
                    'allowed_payment_methods' => [
                        'CASH',
                        'KHALTI',
                        'ESEWA',
                    ],
                ];

                $summary['status_url'] = route(
                    'central.subscription-payments.status',
                    ['payment' => $payment->id]
                );
            }
        } else {
            $remainingAmount = (float) $payment->invoice?->remaining_amount;
            $summary['next_step'] = $remainingAmount > 0
                ? 'Pay the remaining invoice balance to complete the subscription payment.'
                : ($tenant?->status === 'pending'
                    ? 'Invoice paid. Tenant registration is pending central admin review.'
                    : 'Invoice paid. Complete tenant registration using this payment.');

            if ($remainingAmount > 0) {
                $summary['pay'] = $payment->tenant_id !== null
                    ? [
                        'method' => 'POST',
                        'url' => route(
                            'central.subscription-payments.pay',
                            ['payment' => $payment->id]
                        ),
                        'allowed_payment_methods' => ['CASH', 'KHALTI'],
                    ]
                    : [
                        'method' => 'POST',
                        'url' => route('central.subscription-payments.pay-invoice'),
                        'invoice_id' => $payment->invoice?->id,
                        'amount' => $payment->invoice?->remaining_amount,
                        'allowed_payment_methods' => ['KHALTI'],
                    ];
            } elseif (! $tenant) {
                $summary['tenant_registration'] = [
                    'method' => 'POST',
                    'url' => route(
                        'central.subscription-payments.complete-and-create-tenant',
                        ['payment' => $payment->id]
                    ),
                ];
            }

            $summary['status_url'] = route(
                'central.subscription-payments.status',
                [
                    'payment' =>
                    $payment->id,
                ]
            );
        }

        return $summary;
    }

    /**
     * Get configured frontend URL.
     */
    private function normalizePaymentMethod(Request $request): void
    {
        if ($request->filled('payment_method')) {
            $request->merge([
                'payment_method' => strtoupper(
                    trim((string) $request->input('payment_method'))
                ),
            ]);
        }
    }

    private function frontendUrl(): string
    {
        return rtrim(
            config('app.frontend_url'),
            '/'
        );
    }

    /**
     * Redirect Khalti verification to frontend.
     */
    private function paymentWithReturnUrl(
        SubscriptionPayment $payment,
        ?string $returnUrl
    ): SubscriptionPayment {
        if ($returnUrl !== null) {
            $payment->setAttribute('return_url', $returnUrl);
        }

        return $payment;
    }

    private function redirectPaymentFrontend(
        SubscriptionPayment $payment,
        string $returnUrl,
        string $message
    ): RedirectResponse {
        $frontendUrl = rtrim($returnUrl, '/');
        $separator = str_contains($frontendUrl, '?') ? '&' : '?';
        $query = http_build_query([
            'payment' => 'success',
            'payment_id' => $payment->id,
            'subscription_id' => $payment->subscription_id,
            'invoice_id' => $payment->invoice_id,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'message' => $message,
        ]);

        return redirect()->away($frontendUrl . $separator . $query);
    }

    private function redirectKhaltiFrontend(
        SubscriptionPayment $payment,
        string $status,
        string $message,
        ?string $returnUrl = null
    ) {
        /*
         * Use the return_url sent by frontend.
         *
         * If no return_url was provided,
         * use configured frontend URL.
         */
        if (! empty($returnUrl)) {
            $frontendUrl = rtrim(
                $returnUrl,
                '/'
            );
        } else {
            $frontendUrl =
                $this->frontendUrl()
                . '/payment/subscription';
        }

        $query = http_build_query([
            'payment' => $status,

            'payment_id' =>
            $payment->id,

            'subscription_id' =>
            $payment->subscription_id,

            'invoice_id' =>
            $payment->invoice_id,

            'amount' =>
            $payment->amount,

            'status' =>
            $payment->status,

            'message' =>
            $message,
        ]);

        $redirectUrl =
            $frontendUrl .
            '?' .
            $query;

        Log::info(
            'CENTRAL KHALTI FRONTEND REDIRECT',
            [
                'payment_id' =>
                $payment->id,

                'subscription_id' =>
                $payment->subscription_id,

                'invoice_id' =>
                $payment->invoice_id,

                'payment_status' =>
                $payment->status,

                'redirect_url' =>
                $redirectUrl,
            ]
        );

        return redirect()->away(
            $redirectUrl
        );
    }

    /**
     * Redirect eSewa verification to frontend.
     */
    private function redirectEsewaFrontend(
        SubscriptionPayment $payment,
        string $status,
        string $message,
        ?string $returnUrl = null
    ) {
        if (! empty($returnUrl)) {
            $frontendUrl = rtrim(
                $returnUrl,
                '/'
            );
        } else {
            $frontendUrl =
                $this->frontendUrl()
                . '/payment/subscription';
        }

        $query = http_build_query([
            'payment' => $status,

            'payment_id' =>
            $payment->id,

            'subscription_id' =>
            $payment->subscription_id,

            'invoice_id' =>
            $payment->invoice_id,

            'amount' =>
            $payment->amount,

            'status' =>
            $payment->status,

            'message' =>
            $message,
        ]);

        $redirectUrl =
            $frontendUrl .
            '?' .
            $query;

        Log::info(
            'CENTRAL ESEWA FRONTEND REDIRECT',
            [
                'payment_id' =>
                $payment->id,

                'subscription_id' =>
                $payment->subscription_id,

                'invoice_id' =>
                $payment->invoice_id,

                'payment_status' =>
                $payment->status,

                'redirect_url' =>
                $redirectUrl,
            ]
        );

        return redirect()->away(
            $redirectUrl
        );
    }

    /**
     * Mark payment as failed.
     */
    public function fail(
        SubscriptionPayment $payment
    ): JsonResponse {
        if (in_array(
            $payment->status,
            ['SUCCESS', 'COMPLETED'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' =>
                'Cannot fail an already successful payment.',
            ], 422);
        }

        $payment->update([
            'status' => 'FAILED',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
            'Payment marked as failed.',
            'data' => $payment
                ->fresh()
                ->load([
                    'subscription.plan',
                    'tenant',
                    'invoice',
                ]),
        ]);
    }
}
