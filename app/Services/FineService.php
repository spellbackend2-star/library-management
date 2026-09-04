<?php

namespace App\Services;

use App\Models\BookingSeat;
use App\Models\Borrow;
use App\Models\Fine;
use App\Models\LockerAssignment;
use App\Models\Member;
use App\Models\Package;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FineService
{
    public const REASON_OVERDUE = 'overdue';
    public const REASON_SEAT_OVERDUE = 'seat_overdue';
    public const REASON_LOCKER_OVERDUE = 'locker_overdue';
    public const REASON_DAMAGED = 'damaged';
    public const REASON_LOST = 'lost';

    /**
     * If a borrow is returned after its due date, create a fine.
     * Idempotent: if a fine already exists for this borrow + reason, no new fine is created.
     */
    public function fineForBorrowOnReturn(Borrow $borrow, ?string $returnDate = null): ?Fine
    {
        if (!$borrow->due_date) {
            return null;
        }

        $actualReturn = $returnDate
            ? Carbon::parse($returnDate)
            : ($borrow->return_date ? Carbon::parse($borrow->return_date) : Carbon::now());

        $due = Carbon::parse($borrow->due_date)->endOfDay();
        $daysLate = (int) floor($actualReturn->diffInDays($due, false) * -1);

        if ($daysLate <= 0) {
            return null;
        }

        return $this->createOverdueFine(
            member: $borrow->member,
            amount: $this->bookOverdueAmount($borrow->member, $daysLate),
            daysLate: $daysLate,
            reason: self::REASON_OVERDUE,
            borrowId: $borrow->id,
        );
    }

    /**
     * If a seat booking is completed after its end_at, create a fine.
     */
    public function fineForBookingSeatOnComplete(BookingSeat $bookingSeat): ?Fine
    {
        if (!$bookingSeat->end_at) {
            return null;
        }

        $end = Carbon::parse($bookingSeat->end_at);
        $now = Carbon::now();

        if ($now->lessThanOrEqualTo($end)) {
            return null;
        }

        $hoursLate = (int) ceil($now->diffInMinutes($end) / 60) * -1;
        $hoursLate = max(1, $hoursLate);

        return $this->createOverdueFine(
            member: $bookingSeat->member,
            amount: $this->seatOverdueAmount($bookingSeat->member, $hoursLate),
            daysLate: $hoursLate,
            reason: self::REASON_SEAT_OVERDUE,
            bookingSeatId: $bookingSeat->id,
        );
    }

    /**
     * If a locker assignment is returned after its expiry_date, create a fine.
     */
    public function fineForLockerOnReturn(LockerAssignment $assignment, ?string $returnDate = null): ?Fine
    {
        if (!$assignment->expiry_date) {
            return null;
        }

        $actualReturn = $returnDate
            ? Carbon::parse($returnDate)
            : ($assignment->returned_date ? Carbon::parse($assignment->returned_date) : Carbon::now());

        $expiry = Carbon::parse($assignment->expiry_date)->endOfDay();
        $daysLate = (int) floor($actualReturn->diffInDays($expiry, false) * -1);

        if ($daysLate <= 0) {
            return null;
        }

        return $this->createOverdueFine(
            member: $assignment->member,
            amount: $this->lockerOverdueAmount($assignment->member, $daysLate),
            daysLate: $daysLate,
            reason: self::REASON_LOCKER_OVERDUE,
            lockerAssignmentId: $assignment->id,
        );
    }

    protected function createOverdueFine(
        ?Member $member,
        float $amount,
        int $daysLate,
        string $reason,
        ?int $borrowId = null,
        ?int $bookingSeatId = null,
        ?int $lockerAssignmentId = null,
    ): ?Fine {
        if (!$member) {
            return null;
        }

        if ($borrowId && Fine::where('borrow_id', $borrowId)
            ->where('reason', $reason)
            ->exists()) {
            return null;
        }

        if ($bookingSeatId && Fine::where('booking_seat_id', $bookingSeatId)
            ->where('reason', $reason)
            ->exists()) {
            return null;
        }

        if ($lockerAssignmentId && Fine::where('locker_assignment_id', $lockerAssignmentId)
            ->where('reason', $reason)
            ->exists()) {
            return null;
        }

        return DB::transaction(function () use (
            $member, $amount, $reason, $borrowId, $bookingSeatId, $lockerAssignmentId, $daysLate
        ) {
            return Fine::create([
                'member_id' => $member->id,
                'borrow_id' => $borrowId,
                'booking_seat_id' => $bookingSeatId,
                'locker_assignment_id' => $lockerAssignmentId,
                'amount' => $amount,
                'reason' => $reason,
                'issued_date' => Carbon::now()->toDateString(),
                'status' => 'unpaid',
                'days_late' => $daysLate,
            ]);
        });
    }

    protected function package(Member $member): ?Package
    {
        return $member->package;
    }

    protected function bookOverdueAmount(?Member $member, int $daysLate): float
    {
        $perDay = 5.00;

        if ($member && $this->package($member)?->overdue_fine_per_day) {
            $perDay = (float) $this->package($member)->overdue_fine_per_day;
        }

        return round($perDay * $daysLate, 2);
    }

    protected function seatOverdueAmount(?Member $member, int $hoursLate): float
    {
        $perHour = 2.00;

        if ($member && $this->package($member)?->seat_overdue_fine_per_hour) {
            $perHour = (float) $this->package($member)->seat_overdue_fine_per_hour;
        }

        return round($perHour * $hoursLate, 2);
    }

    protected function lockerOverdueAmount(?Member $member, int $daysLate): float
    {
        $perDay = 3.00;

        if ($member && $this->package($member)?->locker_overdue_fine_per_day) {
            $perDay = (float) $this->package($member)->locker_overdue_fine_per_day;
        }

        return round($perDay * $daysLate, 2);
    }
}
