<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Package;
use App\Repositories\Eloquent\MemberRepository;
use App\Repositories\Eloquent\PackageRepository;
use App\Repositories\Interface\BookingInterface;
use App\Repositories\Interface\BookingSeatInterface;
use App\Repositories\Interface\BorrowInterface;
use App\Repositories\Interface\CopyInterface;
use App\Repositories\Interface\CouponInterface;
use App\Repositories\Interface\LockerAssignmentInterface;
use App\Repositories\Interface\LockerInterface;
use App\Repositories\Interface\MemberInterface;
use App\Repositories\Interface\PackageInterface;
use App\Repositories\Interface\SeatInterface;
use App\Services\BookingService;
use App\Services\InvoiceService;
use App\Services\MemberService;
use App\Services\PackageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class PackageLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('invoices');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('members');
        Schema::dropIfExists('packages');

        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('duration')->default(1);
            $table->string('duration_unit')->default('month');
            $table->unsignedInteger('max_book_loans')->nullable();
            $table->unsignedInteger('max_borrow_days')->nullable();
            $table->boolean('seat_access_allowed')->default(true);
            $table->decimal('max_seat_hours_per_day', 4, 2)->nullable();
            $table->boolean('locker_allowed')->default(false);
            $table->string('locker_type')->nullable();
            $table->decimal('max_locker_hours_per_day', 4, 2)->nullable();
            $table->decimal('overdue_fine_per_day', 10, 2)->nullable();
            $table->decimal('seat_overdue_fine_per_hour', 10, 2)->nullable();
            $table->decimal('locker_overdue_fine_per_day', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('retired_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('members', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('package_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('membership_start')->nullable();
            $table->date('membership_expiry')->nullable();
            $table->date('paid_through')->nullable();
            $table->unsignedBigInteger('membership_invoice_id')->nullable();
            $table->unsignedInteger('membership_paid_days')->nullable();
            $table->string('status')->default('active');
            $table->string('gender')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('status');
            $table->string('invoice_type')->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
        });

        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('package_id')->nullable();
        });
    }

    public function test_descriptive_updates_keep_the_package_used_by_current_members(): void
    {
        $package = $this->createPackage();
        $this->createMember($package);

        $updated = $this->packageService()->update($package->id, array_merge(
            $this->packageData(),
            ['description' => 'Corrected description']
        ));

        $this->assertSame($package->id, $updated->id);
        $this->assertSame('Corrected description', $updated->description);
        $this->assertTrue($updated->is_active);
        $this->assertNull($updated->retired_at);
        $this->assertSame(1, Package::query()->count());
    }

    public function test_membership_rule_updates_are_rejected_after_members_have_used_the_package(): void
    {
        $package = $this->createPackage();
        $member = $this->createMember($package);

        try {
            $this->packageService()->update($package->id, [
                'price' => '125.00',
            ]);
            $this->fail('Expected membership rule changes to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('package', $exception->errors());
        }

        $original = $package->fresh();
        $member->refresh();

        $this->assertSame(1, Package::query()->count());
        $this->assertSame('100.00', $original->price);
        $this->assertTrue($original->is_active);
        $this->assertNull($original->retired_at);
        $this->assertSame($package->id, $member->package_id);
    }

    public function test_membership_rule_updates_are_rejected_for_expired_member_history(): void
    {
        $package = $this->createPackage();
        $member = $this->createMember($package, Carbon::today()->subDay()->toDateString());

        try {
            $this->packageService()->update($package->id, [
                'price' => '125.00',
            ]);
            $this->fail('Expected membership rule changes to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('package', $exception->errors());
        }

        $this->assertSame(1, Package::query()->count());
        $this->assertSame($package->id, $member->fresh()->package_id);
        $this->assertSame('100.00', $package->fresh()->price);
        $this->assertTrue($package->fresh()->is_active);
        $this->assertNull($package->fresh()->retired_at);
    }

    public function test_active_status_cannot_change_after_package_history_exists(): void
    {
        $package = $this->createPackage();
        $this->createMember($package);

        try {
            $this->packageService()->update($package->id, [
                'is_active' => false,
            ]);
            $this->fail('Expected package status changes to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('package', $exception->errors());
        }

        $this->assertTrue($package->fresh()->is_active);
    }

    public function test_membership_rule_updates_are_allowed_before_any_package_history_exists(): void
    {
        $package = $this->createPackage();

        $updated = $this->packageService()->update($package->id, [
            'price' => '125.00',
        ]);

        $this->assertSame($package->id, $updated->id);
        $this->assertSame('125.00', $updated->price);
        $this->assertSame(1, Package::query()->count());
    }

    public function test_delete_retires_a_package_until_current_memberships_expire(): void
    {
        $package = $this->createPackage();
        $this->createMember($package);

        $deleted = $this->packageService()->delete($package->id);
        $package->refresh();

        $this->assertFalse($deleted);
        $this->assertFalse($package->is_active);
        $this->assertNotNull($package->retired_at);
        $this->assertNull($package->deleted_at);
        $this->assertCount(0, (new PackageRepository)->all());
    }

    public function test_expired_membership_allows_package_soft_deletion(): void
    {
        $package = $this->createPackage();
        $this->createMember($package, Carbon::today()->subDay()->toDateString());

        $deleted = $this->packageService()->delete($package->id);

        $this->assertTrue($deleted);
        $this->assertNotNull(Package::withTrashed()->find($package->id)?->deleted_at);
    }

    public function test_updating_a_member_with_the_same_retired_package_keeps_its_expiry(): void
    {
        $package = $this->createPackage([
            'is_active' => false,
            'retired_at' => Carbon::today(),
        ]);
        $expiry = Carbon::today()->addDays(45)->toDateString();
        $member = $this->createMember($package, $expiry);

        $updated = (new MemberService(new MemberRepository))->update($member->id, [
            'package_id' => $package->id,
            'phone' => '555-0100',
        ]);

        $this->assertSame($package->id, $updated->package_id);
        $this->assertSame($expiry, $updated->membership_expiry->toDateString());
        $this->assertSame('555-0100', $updated->phone);
    }

    public function test_current_members_can_book_on_a_retired_package_until_expiry(): void
    {
        $member = new Member;
        $member->id = 50;
        $member->status = 'active';
        $member->membership_expiry = Carbon::today();
        $member->setRelation('package', new Package([
            'is_active' => false,
            'retired_at' => Carbon::today(),
        ]));

        $memberRepository = Mockery::mock(MemberInterface::class);
        $memberRepository->shouldReceive('find')
            ->once()
            ->with(50)
            ->andReturn($member);

        $invoiceService = Mockery::mock(InvoiceService::class);
        $invoiceService->shouldReceive('findByMember')
            ->once()
            ->with(50)
            ->andReturn(null);
        $this->app->instance(InvoiceService::class, $invoiceService);

        $service = new BookingService(
            Mockery::mock(BookingInterface::class),
            Mockery::mock(BookingSeatInterface::class),
            Mockery::mock(BorrowInterface::class),
            Mockery::mock(LockerAssignmentInterface::class),
            $memberRepository,
            Mockery::mock(PackageInterface::class),
            Mockery::mock(SeatInterface::class),
            Mockery::mock(CopyInterface::class),
            Mockery::mock(LockerInterface::class),
            Mockery::mock(CouponInterface::class),
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No bookings provided.');

        $service->create([
            'member_id' => 50,
            'bookings' => [],
        ]);
    }

    public function test_members_with_a_partially_paid_invoice_can_book(): void
    {
        $member = new Member;
        $member->id = 51;
        $member->status = 'active';
        $member->membership_expiry = Carbon::today();
        $member->setRelation('package', new Package([
            'is_active' => true,
        ]));

        $memberRepository = Mockery::mock(MemberInterface::class);
        $memberRepository->shouldReceive('find')
            ->once()
            ->with(51)
            ->andReturn($member);

        $partialInvoice = new \App\Models\Invoice([
            'invoice_number' => 'INV-51',
            'status' => 'partially_paid',
            'paid_amount' => '101.00',
        ]);
        $invoiceService = Mockery::mock(InvoiceService::class);
        $invoiceService->shouldReceive('findByMember')
            ->once()
            ->with(51)
            ->andReturn($partialInvoice);
        $this->app->instance(InvoiceService::class, $invoiceService);

        $service = new BookingService(
            Mockery::mock(BookingInterface::class),
            Mockery::mock(BookingSeatInterface::class),
            Mockery::mock(BorrowInterface::class),
            Mockery::mock(LockerAssignmentInterface::class),
            $memberRepository,
            Mockery::mock(PackageInterface::class),
            Mockery::mock(SeatInterface::class),
            Mockery::mock(CopyInterface::class),
            Mockery::mock(LockerInterface::class),
            Mockery::mock(CouponInterface::class),
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No bookings provided.');

        $service->create([
            'member_id' => 51,
            'bookings' => [],
        ]);
    }

    private function packageService(): PackageService
    {
        return new PackageService(new PackageRepository);
    }

    private function createPackage(array $overrides = []): Package
    {
        return Package::create(array_merge($this->packageData(), $overrides));
    }

    private function packageData(): array
    {
        return [
            'name' => 'Gold',
            'description' => 'Gold membership',
            'price' => '100.00',
            'duration' => 30,
            'duration_unit' => 'day',
            'max_book_loans' => 5,
            'max_borrow_days' => 14,
            'seat_access_allowed' => true,
            'max_seat_hours_per_day' => 2,
            'locker_allowed' => false,
            'locker_type' => null,
            'max_locker_hours_per_day' => null,
            'overdue_fine_per_day' => '1.00',
            'seat_overdue_fine_per_hour' => '1.00',
            'locker_overdue_fine_per_day' => '1.00',
            'is_active' => true,
        ];
    }

    private function createMember(Package $package, ?string $expiry = null): Member
    {
        return Member::create([
            'package_id' => $package->id,
            'membership_start' => Carbon::today()->toDateString(),
            'membership_expiry' => $expiry ?? Carbon::today()->addDays(30)->toDateString(),
            'status' => 'active',
        ]);
    }
}
