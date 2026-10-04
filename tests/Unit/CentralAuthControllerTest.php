<?php

namespace Tests\Unit;

use App\Http\Controllers\v1\Central\CentralRegistrationController;
use App\Http\Controllers\v1\Central\CentralSubscriptionPaymentController;
use App\Http\Resources\v1\Central\CentralLoginResource;
use App\Http\Resources\v1\Central\CentralPaymentTenantCompletionResource;
use App\Http\Resources\v1\Central\CentralPlanPaymentInitiationResource;
use App\Models\CentralInvoice;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Http\Requests\Central\CentralRegisterRequest;
use App\Services\CentralAuthService;
use App\Services\Payments\EsewaService;
use App\Services\Payments\KhaltiService;
use Mockery;
use Tests\TestCase;
use Illuminate\Http\Request;

class CentralAuthControllerTest extends TestCase
{
    public function test_login_resource_preserves_roles_and_permissions(): void
    {
        $user = new class
        {
            public int $id = 1;
            public string $name = 'Central Admin';
            public string $email = 'admin@example.com';

            public function getRoleNames()
            {
                return collect(['admin']);
            }

            public function getAllPermissions()
            {
                return collect([(object) ['name' => 'tenant.view']]);
            }
        };

        $payload = response()->json((new CentralLoginResource([
            'token' => ['access_token' => 'test-token'],
            'tenant' => null,
            'user' => $user,
        ]))->resolve(Request::create('/api/v1/central/login')))->getData(true);

        $this->assertSame(['success', 'message', 'token', 'user', 'roles', 'permissions'], array_keys($payload));
        $this->assertSame(['admin'], $payload['roles']);
        $this->assertSame(['tenant.view'], $payload['permissions']);
        $this->assertArrayNotHasKey('tenant', $payload);
    }

    public function test_tenant_registration_returns_requested_response_shape(): void
    {
        $input = [
            'owner' => 'Name',
            'company_name' => 'Company',
            'email' => 'lal@demo.com',
            'password' => 'reader5678',
            'subdomain' => 'radhe4',
            'subscription_plan_id' => 2,
            'coupon_id' => 3,
        ];

        $request = new class extends CentralRegisterRequest
        {
            public function validated($key = null, $default = null)
            {
                return [
                    'owner' => 'Name',
                    'company_name' => 'Company',
                    'email' => 'lal@demo.com',
                    'password' => 'reader5678',
                    'subdomain' => 'radhe4',
                    'subscription_plan_id' => 2,
                    'coupon_id' => 3,
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
            'coupon_id' => 3,
            'coupon_discount' => '250.00',
            'total_amount' => '1750.00',
            'paid_amount' => '0.00',
            'remaining_amount' => '1750.00',
            'currency' => 'NPR',
            'currency_symbol' => 'Rs.',
            'status' => 'unpaid',
            'due_date' => '2026-10-09',
        ]);
        $invoice->id = 24;

        $payment = new SubscriptionPayment([
            'invoice_id' => 24,
            'subscription_id' => 29,
            'amount' => '1750.00',
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

        $response = (new CentralRegistrationController($service))->register($request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(['status', 'message', 'tenant', 'subscription', 'invoice', 'payment', 'next_step'], array_keys($payload));
        $this->assertSame(['id', 'company_name', 'tenant_code', 'owner_email', 'owner_name', 'status', 'domain'], array_keys($payload['tenant']));
        $this->assertSame(['id', 'subscription_plan_id', 'amount', 'status', 'starts_at', 'expires_at', 'plan'], array_keys($payload['subscription']));
        $this->assertSame(['id', 'name', 'price', 'duration', 'duration_unit'], array_keys($payload['subscription']['plan']));
        $this->assertSame(['id', 'invoice_number', 'invoice_type', 'subtotal', 'tax', 'discount', 'coupon_id', 'coupon_discount', 'total_amount', 'paid_amount', 'remaining_amount', 'currency', 'currency_symbol', 'status', 'due_date'], array_keys($payload['invoice']));
        $this->assertSame(['id', 'invoice_id', 'subscription_id', 'amount', 'payment_method', 'status', 'transaction_id', 'paid_at'], array_keys($payload['payment']));
        $this->assertSame(['action', 'payment_id', 'invoice_id', 'message'], array_keys($payload['next_step']));
        $this->assertSame('radhe4.example.com', $payload['tenant']['domain']);
        $this->assertSame(29, $payload['next_step']['payment_id']);
        $this->assertSame(24, $payload['next_step']['invoice_id']);
        $this->assertSame(3, $payload['invoice']['coupon_id']);
        $this->assertSame('250.00', $payload['invoice']['coupon_discount']);
        $this->assertSame('1750.00', $payload['invoice']['total_amount']);
        $this->assertSame('1750.00', $payload['payment']['amount']);
    }

    public function test_completed_payment_can_be_used_to_create_tenant(): void
    {
        $payment = new SubscriptionPayment(['status' => 'SUCCESS']);
        $input = [
            'owner' => 'Name',
            'company_name' => 'Company',
            'email' => 'owner@example.com',
            'password' => 'reader5678',
            'subdomain' => 'newlibrary',
        ];

        $service = Mockery::mock(CentralAuthService::class);
        $plan = new SubscriptionPlan([
            'name' => 'Basic1',
            'price' => '2000.00',
            'duration' => 1,
            'duration_unit' => 'year',
        ]);
        $plan->id = 4;

        $subscription = new Subscription([
            'amount' => '2000.00',
            'starts_at' => '2026-10-04',
            'expires_at' => '2027-10-04',
            'status' => 'active',
        ]);
        $subscription->id = 66;
        $subscription->setRelation('plan', $plan);

        $invoice = new CentralInvoice([
            'invoice_number' => 'INV-2026-60',
            'subtotal' => '2000.00',
            'coupon_id' => 5,
            'coupon_discount' => '100.00',
            'total_amount' => '1900.00',
            'paid_amount' => '1900.00',
            'remaining_amount' => '0.00',
            'status' => 'paid',
        ]);
        $invoice->id = 60;

        $tenant = new Tenant([
            'id' => 'f9d5f295-3878-4de5-819d-366b25330ae9',
            'company_name' => 'nepaLibrary',
            'tenant_code' => 'radhe1',
            'owner_name' => 'radhe',
            'owner_email' => 'owner@example.com',
            'status' => 'active',
        ]);

        $payment->id = 64;
        $payment->amount = '1900.00';
        $payment->payment_method = 'KHALTI';
        $payment->transaction_id = 'AgidLxrprTyXEAJHWiJP66';
        $payment->paid_at = '2026-10-04T10:49:34.000000Z';

        $service->shouldReceive('completePaymentAndCreateTenant')
            ->once()
            ->with($payment, $input)
            ->andReturn([
                'subscription_payment' => $payment,
                'invoice' => $invoice,
                'tenant' => $tenant,
                'domain' => 'radhe1.example.com',
                'subscription' => $subscription,
            ]);

        $controller = new CentralSubscriptionPaymentController(
            $service,
            Mockery::mock(KhaltiService::class),
            Mockery::mock(EsewaService::class),
        );

        $request = new class extends Request
        {
            public function validate(array $rules, array $messages = [], array $attributes = []): array
            {
                return $this->all();
            }
        };
        $request->replace($input);

        $response = $controller->completeAndCreateTenant($request, $payment);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame(
            'Payment completed and tenant activated successfully.',
            $payload['message']
        );
        $this->assertSame([
            'payment',
            'invoice',
            'tenant',
            'domain',
            'subscription',
        ], array_keys($payload['data']));
        $this->assertSame(5, $payload['data']['invoice']['coupon_id']);
        $this->assertSame('100.00', $payload['data']['invoice']['coupon_discount']);
        $this->assertSame('1900.00', $payload['data']['payment']['amount']);
        $this->assertSame('radhe1.example.com', $payload['data']['domain']);
        $this->assertSame('active', $payload['data']['subscription']['status']);
    }

    public function test_payment_tenant_completion_resource_matches_response_contract(): void
    {
        $plan = new SubscriptionPlan([
            'name' => 'Basic1',
            'duration' => 1,
            'duration_unit' => 'year',
        ]);
        $plan->id = 4;

        $subscription = new Subscription([
            'amount' => '2000.00',
            'starts_at' => '2026-10-04',
            'expires_at' => '2027-10-04',
            'status' => 'active',
        ]);
        $subscription->id = 66;
        $subscription->setRelation('plan', $plan);

        $invoice = new CentralInvoice([
            'invoice_number' => 'INV-2026-60',
            'subtotal' => '2000.00',
            'coupon_id' => 5,
            'coupon_discount' => '100.00',
            'total_amount' => '1900.00',
            'paid_amount' => '1900.00',
            'remaining_amount' => '0.00',
            'status' => 'paid',
        ]);
        $invoice->id = 60;

        $tenant = new Tenant([
            'id' => 'f9d5f295-3878-4de5-819d-366b25330ae9',
            'company_name' => 'nepaLibrary',
            'tenant_code' => 'radhe1',
            'owner_name' => 'radhe',
            'owner_email' => 'owner@example.com',
            'status' => 'active',
        ]);

        $payment = new SubscriptionPayment([
            'amount' => '1900.00',
            'payment_method' => 'KHALTI',
            'status' => 'SUCCESS',
            'transaction_id' => 'AgidLxrprTyXEAJHWiJP66',
            'paid_at' => '2026-10-04T10:49:34.000000Z',
        ]);
        $payment->id = 64;

        $payload = (new CentralPaymentTenantCompletionResource([
            'subscription_payment' => $payment,
            'invoice' => $invoice,
            'tenant' => $tenant,
            'domain' => 'radhe1.example.com',
            'subscription' => $subscription,
        ]))->resolve(Request::create('/api/v1/central/subscription-payments/64/complete-and-create-tenant'));

        $this->assertSame([
            'payment',
            'invoice',
            'tenant',
            'domain',
            'subscription',
        ], array_keys($payload));
        $this->assertSame(64, $payload['payment']['id']);
        $this->assertSame('SUCCESS', $payload['payment']['status']);
        $this->assertSame(5, $payload['invoice']['coupon_id']);
        $this->assertSame('100.00', $payload['invoice']['coupon_discount']);
        $this->assertSame('active', $payload['tenant']['status']);
        $this->assertSame('radhe1.example.com', $payload['domain']);
        $this->assertSame(4, $payload['subscription']['plan_id']);
        $this->assertSame('Basic1', $payload['subscription']['plan_name']);
    }

    public function test_plan_payment_initiation_resource_returns_expected_shape_and_gateway_data(): void
    {
        $plan = new SubscriptionPlan([
            'name' => 'Basic1',
            'price' => '2000.00',
            'duration' => 1,
            'duration_unit' => 'year',
        ]);
        $plan->id = 4;

        $subscription = new Subscription([
            'amount' => '2000.00',
            'status' => 'pending',
        ]);
        $subscription->id = 62;
        $subscription->setRelation('plan', $plan);

        $invoice = new CentralInvoice([
            'invoice_number' => 'INV-2026-56',
            'subtotal' => '2000.00',
            'coupon_id' => 5,
            'coupon_discount' => '100.00',
            'total_amount' => '1900.00',
            'paid_amount' => '0.00',
            'remaining_amount' => '1900.00',
            'status' => 'unpaid',
            'due_date' => '2026-10-11',
        ]);
        $invoice->id = 56;

        $payment = new SubscriptionPayment([
            'amount' => '1900.00',
            'payment_method' => 'KHALTI',
            'status' => 'PENDING',
            'transaction_id' => 'V8jcGevycdiEofdcQVG6FR',
        ]);
        $payment->id = 60;

        $payload = (new CentralPlanPaymentInitiationResource([
            'subscription' => $subscription,
            'invoice' => $invoice,
            'payment' => $payment,
            'gateway' => ['payment_url' => 'https://pay.example.com'],
        ]))->resolve(Request::create('/api/v1/central/subscription-payments/initiate-from-plan'));

        $this->assertSame([
            'subscription',
            'invoice',
            'payment',
            'gateway',
        ], array_keys($payload));
        $this->assertSame([
            'id',
            'plan_id',
            'plan_name',
            'amount',
            'duration',
            'duration_unit',
            'status',
        ], array_keys($payload['subscription']));
        $this->assertSame([
            'id',
            'invoice_number',
            'subtotal',
            'coupon_id',
            'coupon_discount',
            'total_amount',
            'paid_amount',
            'remaining_amount',
            'status',
            'due_date',
        ], array_keys($payload['invoice']));
        $this->assertSame([
            'id',
            'amount',
            'payment_method',
            'status',
            'transaction_id',
        ], array_keys($payload['payment']));
        $this->assertSame(5, $payload['invoice']['coupon_id']);
        $this->assertSame('1900.00', $payload['payment']['amount']);
        $this->assertSame('https://pay.example.com', $payload['gateway']['payment_url']);
    }
}