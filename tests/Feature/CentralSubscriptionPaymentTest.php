<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\CentralInvoice;
use App\Models\Coupon;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CentralSubscriptionPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_subscription_payment_route_exists(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::create([
            'name' => 'Starter',
            'description' => 'Starter plan',
            'price' => 1500,
            'duration' => 1,
            'duration_unit' => 'month',
            'is_active' => true,
        ]);

        $tenant = Tenant::create([
            'company_name' => 'Test Company',
            'tenant_code' => 'testcompany',
            'owner_email' => 'owner@testcompany.com',
            'status' => 'inactive',
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'amount' => 1500,
            'status' => 'pending',
        ]);

        Passport::actingAs($user, ['*']);

        $response = $this->postJson('/api/central/subscription-payments', [
            'subscription_id' => $subscription->id,
            'payment_method' => 'CASH',
            'amount' => 1500,
        ]);

        $response->assertStatus(200);
    }

    public function test_payment_status_includes_coupon_details_on_invoice(): void
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Coupon plan',
            'description' => 'Plan with a coupon',
            'price' => 1500,
            'duration' => 1,
            'duration_unit' => 'month',
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'subscription_plan_id' => $plan->id,
            'amount' => 1500,
            'status' => 'pending',
        ]);

        $coupon = Coupon::create([
            'code' => 'SAVE100',
            'discount_type' => 'FLAT',
            'discount_value' => 100,
            'used_count' => 1,
            'is_active' => true,
        ]);

        $invoice = CentralInvoice::create([
            'subscription_id' => $subscription->id,
            'invoice_number' => CentralInvoice::generateInvoiceNumber(),
            'invoice_type' => 'subscription',
            'subtotal' => 1500,
            'coupon_id' => $coupon->id,
            'coupon_discount' => 100,
            'total_amount' => 1400,
            'paid_amount' => 1400,
            'remaining_amount' => 0,
            'status' => 'paid',
        ]);

        $payment = SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'invoice_id' => $invoice->id,
            'amount' => 1400,
            'payment_method' => 'KHALTI',
            'status' => 'SUCCESS',
            'paid_at' => now(),
        ]);

        $invoice->update([
            'subscription_payment_id' => $payment->id,
        ]);

        $this->getJson("/api/central/subscription-payments/{$payment->id}/status")
            ->assertOk()
            ->assertJsonPath('data.invoice.coupon_id', $coupon->id)
            ->assertJsonPath('data.invoice.coupon_discount', '100.00');
    }
}
