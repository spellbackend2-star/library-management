<?php

namespace Tests\Feature;

use App\Repositories\Eloquent\MemberRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MemberPaidInvoiceFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function test_member_lists_can_filter_for_paid_or_partially_paid_invoices(): void
    {
        $now = now();
        $paidMemberId = DB::table('members')->insertGetId([
            'first_name' => 'Paid',
            'last_name' => 'Member',
            'email' => 'paid@example.test',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $unpaidMemberId = DB::table('members')->insertGetId([
            'first_name' => 'Unpaid',
            'last_name' => 'Member',
            'email' => 'unpaid@example.test',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $partiallyPaidMemberId = DB::table('members')->insertGetId([
            'first_name' => 'Partially Paid',
            'last_name' => 'Member',
            'email' => 'partial@example.test',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $noInvoiceMemberId = DB::table('members')->insertGetId([
            'first_name' => 'No',
            'last_name' => 'Invoice',
            'email' => 'none@example.test',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $suspendedPaidMemberId = DB::table('members')->insertGetId([
            'first_name' => 'Suspended',
            'last_name' => 'Member',
            'email' => 'suspended@example.test',
            'status' => 'suspended',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('invoices')->insert([
            [
                'member_id' => $paidMemberId,
                'status' => 'paid',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'member_id' => $unpaidMemberId,
                'status' => 'unpaid',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'member_id' => $partiallyPaidMemberId,
                'status' => 'partially_paid',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'member_id' => $suspendedPaidMemberId,
                'status' => 'paid',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $repository = new MemberRepository;

        $paidMembers = $repository->getAll(['has_paid_invoice' => 'true']);
        $membersWithoutPaidInvoices = $repository->getAll(['has_paid_invoice' => 'false']);
        $allPaidMembers = $repository->all(['has_paid_invoice' => '1']);
        $activePaidMembers = $repository->all([
            'status' => 'active',
            'has_paid_invoice' => 'true',
        ]);

        $this->assertEqualsCanonicalizing(
            [$paidMemberId, $partiallyPaidMemberId, $suspendedPaidMemberId],
            array_map(fn ($member) => $member->id, $paidMembers['data'])
        );
        $this->assertEqualsCanonicalizing(
            [$unpaidMemberId, $noInvoiceMemberId],
            array_map(fn ($member) => $member->id, $membersWithoutPaidInvoices['data'])
        );
        $this->assertEqualsCanonicalizing(
            [$paidMemberId, $partiallyPaidMemberId, $suspendedPaidMemberId],
            $allPaidMembers->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$paidMemberId, $partiallyPaidMemberId],
            $activePaidMembers->pluck('id')->all()
        );
    }
}
