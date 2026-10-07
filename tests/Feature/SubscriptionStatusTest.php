<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class SubscriptionStatusTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsApiUser(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['*']);
    }

    private function createPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Test Plan',
            'description' => 'Test plan',
            'price' => 1000,
            'duration' => 1,
            'duration_unit' => 'month',
            'is_active' => true,
        ]);
    }

    public function test_new_subscription_is_pending(): void
    {
        $plan = $this->createPlan();

        $response = $this->postJson('/api/v1/subscriptions', [
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'amount' => 1000,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_pending_subscription_can_be_activated(): void
    {
        $this->actingAsApiUser();
        $plan = $this->createPlan();

        $subscription = Subscription::create([
            'subscription_plan_id' => $plan->id,
            'amount' => 1000,
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/api/v1/subscriptions/{$subscription->id}/status", [
            'status' => 'active',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_pending_subscription_can_be_cancelled(): void
    {
        $this->actingAsApiUser();
        $plan = $this->createPlan();

        $subscription = Subscription::create([
            'subscription_plan_id' => $plan->id,
            'amount' => 1000,
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/api/v1/subscriptions/{$subscription->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_active_subscription_can_be_cancelled(): void
    {
        $this->actingAsApiUser();
        $plan = $this->createPlan();

        $subscription = Subscription::create([
            'subscription_plan_id' => $plan->id,
            'amount' => 1000,
            'status' => 'active',
        ]);

        $response = $this->patchJson("/api/v1/subscriptions/{$subscription->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_expired_subscription_cannot_be_cancelled(): void
    {
        $this->actingAsApiUser();
        $plan = $this->createPlan();

        $subscription = Subscription::create([
            'subscription_plan_id' => $plan->id,
            'amount' => 1000,
            'status' => 'expired',
        ]);

        $response = $this->patchJson("/api/v1/subscriptions/{$subscription->id}/cancel");

        $response->assertStatus(422);
    }

    public function test_cancelled_subscription_cannot_be_activated(): void
    {
        $this->actingAsApiUser();
        $plan = $this->createPlan();

        $subscription = Subscription::create([
            'subscription_plan_id' => $plan->id,
            'amount' => 1000,
            'status' => 'cancelled',
        ]);

        $response = $this->patchJson("/api/v1/subscriptions/{$subscription->id}/status", [
            'status' => 'active',
        ]);

        $response->assertStatus(422);
    }

    public function test_expired_subscription_cannot_be_set_to_expired_again(): void
    {
        $this->actingAsApiUser();
        $plan = $this->createPlan();

        $subscription = Subscription::create([
            'subscription_plan_id' => $plan->id,
            'amount' => 1000,
            'status' => 'expired',
        ]);

        $response = $this->patchJson("/api/v1/subscriptions/{$subscription->id}/status", [
            'status' => 'expired',
        ]);

        $response->assertStatus(422);
    }

    public function test_generic_update_does_not_allow_expired_status(): void
    {
        $this->actingAsApiUser();
        $plan = $this->createPlan();

        $subscription = Subscription::create([
            'subscription_plan_id' => $plan->id,
            'amount' => 1000,
            'status' => 'active',
        ]);

        $response = $this->patchJson("/api/v1/subscriptions/{$subscription->id}", [
            'status' => 'expired',
        ]);

        $response->assertStatus(422);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->actingAsApiUser();
        $plan = $this->createPlan();

        $subscription = Subscription::create([
            'subscription_plan_id' => $plan->id,
            'amount' => 1000,
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/api/v1/subscriptions/{$subscription->id}/status", [
            'status' => 'suspended',
        ]);

        $response->assertStatus(422);
    }
}
