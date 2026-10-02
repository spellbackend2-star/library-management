<?php

namespace Tests\Unit;

use App\Http\Controllers\v1\Central\CentralAuthController;
use App\Models\CentralInvoice;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\CentralAuthService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class CentralAuthControllerTest extends TestCase
{
    public function test_tenant_registration_returns_requested_response_shape(): void
    {
        $input = [
            'owner' => 'Name',
            'company_name' => 'Company',
            'email' => 'lal@demo.com',
            'password' => 'reader5678',
            'subdomain' => 'radhe4',
            'subscription_plan_id' => 2,
        ];

        $request = new class extends Request
        {
            public function validate(array $rules, array $messages = [], array $attributes = []): array
            {
                return [
                    'owner' => 'Name',
                    'company_name' => 'Company',
                    'email' => 'lal@demo.com',
                    'password' => 'reader5678',
                    'subdomain' => 'radhe4',
                    'subscription_plan_id' => 2,
                ];
            }
        };

        $tenant = new Tenant([
            'id' => 'd44c76ff-32f7-4c43-9759-3a30918729f4',
            'company_name' => 'Company',
            'tenant_code' => 'radhe4',
            'owner_email' => 'lal@demo.com',
            'owner_name' => 'Name',
            'status' => 'inactive',
        ]);

        $plan = new SubscriptionPlan([
            'name' => 'Basic',
            'price' => '2000.00',
            'duration' => 1,
            'duration_unit' => 'month',
        ]);
        $plan->id = 2;

        $subscription = new Subscription([
            'subscription_plan_id' => 2,
            'amount' => '2000.00',
            'status' => 'pending',
            'starts_at' => null,
            'expires_at' => null,
        ]);
        $subscription->id = 29;
        $subscription->setRelation('plan', $plan);

        $invoice = new CentralInvoice([
            'invoice_number' => 'INV-2026-20',
            'invoice_type' => 'subscription',
            'subtotal' => '2000.00',
            'tax' => '0.00',
            'discount' => '0.00',
            'total_amount' => '2000.00',
            'paid_amount' => '0.00',
            'remaining_amount' => '2000.00',
            'currency' => 'NPR',
            'currency_symbol' => 'Rs.',
            'status' => 'unpaid',
            'due_date' => '2026-10-09',
        ]);
        $invoice->id = 24;

        $payment = new SubscriptionPayment([
            'invoice_id' => 24,
            'subscription_id' => 29,
            'amount' => '2000.00',
            'payment_method' => 'CASH',
            'status' => 'PENDING',
            'transaction_id' => null,
            'paid_at' => null,
        ]);
        $payment->id = 29;

        $service = Mockery::mock(CentralAuthService::class);
        $service->shouldReceive('registerTenant')->once()->with($input)->andReturn([
            'tenant' => $tenant,
            'domain' => 'radhe4.example.com',
            'subscription' => $subscription,
            'invoice' => $invoice,
            'subscription_payment' => $payment,
        ]);

        $response = (new CentralAuthController($service))->register($request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(['status', 'message', 'tenant', 'subscription', 'invoice', 'payment', 'next_step'], array_keys($payload));
        $this->assertSame(['id', 'company_name', 'tenant_code', 'owner_email', 'owner_name', 'status', 'domain'], array_keys($payload['tenant']));
        $this->assertSame(['id', 'subscription_plan_id', 'amount', 'status', 'starts_at', 'expires_at', 'plan'], array_keys($payload['subscription']));
        $this->assertSame(['id', 'name', 'price', 'duration', 'duration_unit'], array_keys($payload['subscription']['plan']));
        $this->assertSame(['id', 'invoice_number', 'invoice_type', 'subtotal', 'tax', 'discount', 'total_amount', 'paid_amount', 'remaining_amount', 'currency', 'currency_symbol', 'status', 'due_date'], array_keys($payload['invoice']));
        $this->assertSame(['id', 'invoice_id', 'subscription_id', 'amount', 'payment_method', 'status', 'transaction_id', 'paid_at'], array_keys($payload['payment']));
        $this->assertSame(['action', 'payment_id', 'invoice_id', 'message'], array_keys($payload['next_step']));
        $this->assertSame('radhe4.example.com', $payload['tenant']['domain']);
        $this->assertSame(29, $payload['next_step']['payment_id']);
        $this->assertSame(24, $payload['next_step']['invoice_id']);
    }
}