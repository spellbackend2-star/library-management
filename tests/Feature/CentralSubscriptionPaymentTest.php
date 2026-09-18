<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
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
}
