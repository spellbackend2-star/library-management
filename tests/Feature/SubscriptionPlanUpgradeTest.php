<?php

namespace Tests\Feature;

use App\Models\CentralInvoice;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\Central\CentralPaymentService;
use App\Services\Central\CentralSubscriptionPlanChangeService;
use App\Services\Central\PlanChangeSettlementRequired;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubscriptionPlanUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_same_and_lower_priced_plans_are_rejected(): void
    {
        $currentPlan = $this->createPlan('Current', 1200, 1, 'month');
        $lowerPlan = $this->createPlan('Lower', 900, 1, 'month');
        $subscription = $this->createSubscription($currentPlan, $this->createTenant());
        $service = app(CentralSubscriptionPlanChangeService::class);

        try {
            $service->changePlan($subscription->id, $currentPlan->id);
            $this->fail('Changing to the same plan should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('subscription_plan_id', $exception->errors());
        }

        try {
            $service->changePlan($subscription->id, $lowerPlan->id);
            $this->fail('Changing to a lower-priced plan should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('subscription_plan_id', $exception->errors());
        }

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_paid_subscription_credit_is_applied_and_new_term_uses_upgrade_plan_duration(): void
    {
        Carbon::setTestNow('2026-01-11 12:00:00');
        $tenant = $this->createTenant();
        $currentPlan = $this->createPlan('Current', 1200, 1, 'month');
        $upgradePlan = $this->createPlan('Premium', 2400, 3, 'month');
        $subscription = $this->createSubscription(
            $currentPlan,
            $tenant,
            'active',
            '2026-01-01',
            '2026-02-01'
        );
        $this->createInvoiceWithPayment($subscription, $tenant, 1200, 'paid');

        $upgraded = app(CentralSubscriptionPlanChangeService::class)
            ->changePlan($subscription->id, $upgradePlan->id);

        $this->assertSame('pending', $upgraded->status);
        $this->assertSame('2026-01-11', Carbon::parse($upgraded->starts_at)->toDateString());
        $this->assertSame('2026-04-11', Carbon::parse($upgraded->expires_at)->toDateString());
        $this->assertSame($subscription->id, $upgraded->replaces_subscription_id);
        $this->assertSame('active', $subscription->fresh()->status);

        $upgradeInvoice = $upgraded->invoices()->where('invoice_type', 'plan_change')->firstOrFail();
        $expectedCredit = round(1200 * (20 / 31), 2);
        $this->assertEqualsWithDelta($expectedCredit, (float) $upgradeInvoice->discount, 0.01);
        $this->assertEqualsWithDelta(2400 - $expectedCredit, (float) $upgradeInvoice->total_amount, 0.01);
        $this->assertSame(2400.0, (float) $upgradeInvoice->subtotal);

        $payment = SubscriptionPayment::create([
            'subscription_id' => $upgraded->id,
            'invoice_id' => $upgradeInvoice->id,
            'tenant_id' => $tenant->id,
            'amount' => 100,
            'payment_method' => 'CASH',
            'status' => 'PENDING',
        ]);

        app(CentralPaymentService::class)->completeCentralCashPayment($payment);

        $this->assertSame('active', $upgraded->fresh()->status);
        $this->assertSame('cancelled', $subscription->fresh()->status);
        $this->assertSame('partially_paid', $upgradeInvoice->fresh()->status);
        $this->assertSame('2026-04-11', Carbon::parse($upgraded->fresh()->expires_at)->toDateString());
    }

    public function test_unpaid_dues_require_settlement_without_turning_settlement_into_upgrade_credit(): void
    {
        Carbon::setTestNow('2026-01-11 12:00:00');
        $tenant = $this->createTenant();
        $currentPlan = $this->createPlan('Current', 1200, 1, 'month');
        $upgradePlan = $this->createPlan('Premium', 2400, 3, 'month');
        $subscription = $this->createSubscription(
            $currentPlan,
            $tenant,
            'active',
            '2026-01-01',
            '2026-02-01'
        );
        $this->createInvoiceWithPayment($subscription, $tenant, 100, 'partially_paid');
        $service = app(CentralSubscriptionPlanChangeService::class);

        try {
            $service->changePlan($subscription->id, $upgradePlan->id);
            $this->fail('Outstanding dues should require settlement before upgrading.');
        } catch (PlanChangeSettlementRequired $exception) {
            $settlementInvoice = $exception->invoice;
            $this->assertEqualsWithDelta(325.81, (float) $settlementInvoice->remaining_amount, 0.01);
        }

        try {
            $service->changePlan($subscription->id, $upgradePlan->id);
            $this->fail('Unpaid dues should continue to block the upgrade.');
        } catch (PlanChangeSettlementRequired $exception) {
            $this->assertSame($settlementInvoice->id, $exception->invoice->id);
        }

        $this->assertSame(1, $subscription->invoices()->where('invoice_type', 'plan_change_settlement')->count());

        $partialSettlementPayment = SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'invoice_id' => $settlementInvoice->id,
            'tenant_id' => $tenant->id,
            'amount' => 100,
            'payment_method' => 'CASH',
            'status' => 'PENDING',
        ]);
        app(CentralPaymentService::class)->completeCentralCashPayment($partialSettlementPayment);

        try {
            $service->changePlan($subscription->id, $upgradePlan->id);
            $this->fail('The unpaid portion of settlement should still block the upgrade.');
        } catch (PlanChangeSettlementRequired $exception) {
            $this->assertEqualsWithDelta(225.81, (float) $exception->invoice->remaining_amount, 0.01);
        }

        $remainingSettlementPayment = SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'invoice_id' => $settlementInvoice->id,
            'tenant_id' => $tenant->id,
            'amount' => (float) $settlementInvoice->fresh()->remaining_amount,
            'payment_method' => 'CASH',
            'status' => 'PENDING',
        ]);
        app(CentralPaymentService::class)->completeCentralCashPayment($remainingSettlementPayment);

        $upgraded = $service->changePlan($subscription->id, $upgradePlan->id);
        $upgradeInvoice = $upgraded->invoices()->where('invoice_type', 'plan_change')->firstOrFail();

        $this->assertSame(0.0, (float) $upgradeInvoice->discount);
        $this->assertSame(2400.0, (float) $upgradeInvoice->total_amount);
        $this->assertSame(1, $subscription->invoices()->where('invoice_type', 'plan_change_settlement')->count());
    }

    public function test_a_pending_upgrade_cannot_create_a_second_upgrade_invoice(): void
    {
        $tenant = $this->createTenant();
        $currentPlan = $this->createPlan('Current', 1200, 1, 'month');
        $firstUpgradePlan = $this->createPlan('Premium', 2400, 1, 'month');
        $secondUpgradePlan = $this->createPlan('Enterprise', 3600, 1, 'month');
        $subscription = $this->createSubscription(
            $currentPlan,
            $tenant,
            'active',
            now()->subDays(2)->toDateString(),
            now()->addDays(28)->toDateString()
        );
        $this->createInvoiceWithPayment($subscription, $tenant, 1200, 'paid');

        $service = app(CentralSubscriptionPlanChangeService::class);
        $service->changePlan($subscription->id, $firstUpgradePlan->id);

        try {
            $service->changePlan($subscription->id, $secondUpgradePlan->id);
            $this->fail('A second pending upgrade should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('subscription_plan_id', $exception->errors());
        }

        $this->assertSame(1, Subscription::query()
            ->where('replaces_subscription_id', $subscription->id)
            ->count());
        $this->assertSame(2, CentralInvoice::query()->count());
    }

    private function createPlan(
        string $name,
        float $price,
        int $duration,
        string $durationUnit
    ): SubscriptionPlan {
        return SubscriptionPlan::create([
            'name' => $name,
            'description' => $name.' plan',
            'price' => $price,
            'duration' => $duration,
            'duration_unit' => $durationUnit,
            'is_active' => true,
        ]);
    }

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'company_name' => 'Upgrade Test Library',
            'tenant_code' => 'upgrade-test',
            'owner_email' => 'upgrade@example.test',
            'status' => 'active',
        ]);
    }

    private function createSubscription(
        SubscriptionPlan $plan,
        Tenant $tenant,
        string $status = 'active',
        ?string $startsAt = null,
        ?string $expiresAt = null
    ): Subscription {
        return Subscription::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'amount' => $plan->price,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'status' => $status,
        ]);
    }

    private function createInvoiceWithPayment(
        Subscription $subscription,
        Tenant $tenant,
        float $paidAmount,
        string $invoiceStatus
    ): CentralInvoice {
        $invoiceTotal = 1200.0;
        $invoice = CentralInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => CentralInvoice::generateInvoiceNumber(),
            'invoice_type' => 'subscription',
            'subtotal' => $invoiceTotal,
            'total_amount' => $invoiceTotal,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $invoiceTotal - $paidAmount,
            'status' => $invoiceStatus,
        ]);

        if ($paidAmount > 0) {
            $payment = SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
                'tenant_id' => $tenant->id,
                'amount' => $paidAmount,
                'payment_method' => 'CASH',
                'status' => 'SUCCESS',
                'paid_at' => now(),
            ]);

            $invoice->update(['subscription_payment_id' => $payment->id]);
        }

        return $invoice;
    }
}
