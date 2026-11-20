<?php

namespace Tests\Unit;

use App\Http\Resources\CopyResource;
use App\Models\Borrow;
use App\Models\Copy;
use App\Models\Member;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CopyResourceTest extends TestCase
{
    public function test_it_includes_the_current_member_borrower_for_an_on_loan_copy(): void
    {
        $member = new Member([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
        ]);
        $member->id = 7;

        $borrow = new Borrow([
            'member_id' => 7,
            'status' => 'active',
            'checkout_date' => Carbon::parse('2026-11-18 10:00:00'),
            'due_date' => Carbon::parse('2026-11-25'),
        ]);
        $borrow->id = 12;
        $borrow->setRelation('member', $member);
        $borrow->setRelation('staffBorrower', null);

        $copy = new Copy(['status' => 'on_loan']);
        $copy->setRelation('edition', null);
        $copy->setRelation('currentBorrows', new Collection([$borrow]));

        $data = (new CopyResource($copy))->toArray(Request::create('/'));

        $this->assertSame([
            'id' => 12,
            'status' => 'active',
            'member_id' => 7,
            'member_name' => 'Ada Lovelace',
            'staff_id' => null,
            'staff_name' => null,
            'checkout_date' => '2026-11-18 10:00:00',
            'due_date' => '2026-11-25',
        ], $data['current_borrow']);
    }

    public function test_it_reports_the_staff_borrower_id_and_name(): void
    {
        $staffBorrower = new Staff([
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
        ]);
        $staffBorrower->id = 9;

        $borrow = new Borrow([
            'staff_borrower_id' => 9,
            'status' => 'overdue',
        ]);
        $borrow->id = 13;
        $borrow->setRelation('member', null);
        $borrow->setRelation('staffBorrower', $staffBorrower);

        $copy = new Copy(['status' => 'on_loan']);
        $copy->setRelation('edition', null);
        $copy->setRelation('currentBorrows', new Collection([$borrow]));

        $data = (new CopyResource($copy))->toArray(Request::create('/'));

        $this->assertSame(9, $data['current_borrow']['staff_id']);
        $this->assertSame('Grace Hopper', $data['current_borrow']['staff_name']);
        $this->assertNull($data['current_borrow']['member_id']);
        $this->assertNull($data['current_borrow']['member_name']);
    }

    public function test_available_copies_do_not_report_a_current_borrower(): void
    {
        $copy = new Copy(['status' => 'available']);
        $copy->setRelation('edition', null);
        $copy->setRelation('currentBorrows', new Collection);

        $data = (new CopyResource($copy))->toArray(Request::create('/'));

        $this->assertNull($data['current_borrow']);
    }
}
