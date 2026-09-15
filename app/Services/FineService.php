<?php

namespace App\Services;

use App\Models\BookingSeat;
use App\Models\Borrow;
use App\Models\Fine;
use App\Models\Invoice;
use App\Models\LockerAssignment;
use App\Models\Member;
use App\Models\Package;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FineService
{
    public const INVOICE_TYPE_FINE = 'fine';

    public const REASON_OVERDUE = 'overdue';

    public const REASON_SEAT_OVERDUE = 'seat_overdue';

    public const REASON_LOCKER_OVERDUE = 'locker_overdue';

    public const REASON_DAMAGED = 'damaged';

    public const REASON_LOST = 'lost';

    /**
     * Create an overdue fine when a book is returned.
     */
    public function fineForBorrowOnReturn(
        Borrow $borrow,
        ?string $returnDate = null
    ): ?Fine {
        if (!$borrow->due_date) {
            return null;
        }

        $daysLate = max(
            0,
            (int) Carbon::parse($borrow->due_date)
                ->startOfDay()
                ->diffInDays(Carbon::now()->startOfDay())
        );

        if ($daysLate <= 0) {
            return null;
        }

        return $this->createOverdueFine(
            member: $borrow->member,
            amount: $this->bookOverdueAmount(
                member: $borrow->member,
                daysLate: $daysLate
            ),
            daysLate: $daysLate,
            reason: self::REASON_OVERDUE,
            borrowId: $borrow->id,
        );
    }

    /**
     * Create an overdue fine when a seat booking is completed.
     */
    public function fineForBookingSeatOnComplete(
        BookingSeat $bookingSeat
    ): ?Fine {
        if (!$bookingSeat->end_at) {
            return null;
        }

        $end = Carbon::parse($bookingSeat->end_at);
        $now = Carbon::now();

        if ($now->lessThanOrEqualTo($end)) {
            return null;
        }

        $hoursLate = max(
            1,
            (int) ceil(
                $end->diffInMinutes($now) / 60
            )
        );

        return $this->createOverdueFine(
            member: $bookingSeat->member,
            amount: $this->seatOverdueAmount(
                member: $bookingSeat->member,
                hoursLate: $hoursLate
            ),
            daysLate: $hoursLate,
            reason: self::REASON_SEAT_OVERDUE,
            bookingSeatId: $bookingSeat->id,
        );
    }

    /**
     * Create an overdue fine when a locker is returned.
     */
    public function fineForLockerOnReturn(
        LockerAssignment $assignment,
        ?string $returnDate = null
    ): ?Fine {
        if (!$assignment->expiry_date) {
            return null;
        }

        $actualReturn = $returnDate
            ? Carbon::parse($returnDate)
            : (
                $assignment->returned_date
                    ? Carbon::parse($assignment->returned_date)
                    : Carbon::now()
            );

        $expiry = Carbon::parse($assignment->expiry_date)->endOfDay();

        $daysLate = max(
            0,
            (int) $expiry->diffInDays($actualReturn)
        );

        if ($daysLate <= 0) {
            return null;
        }

        return $this->createOverdueFine(
            member: $assignment->member,
            amount: $this->lockerOverdueAmount(
                member: $assignment->member,
                daysLate: $daysLate
            ),
            daysLate: $daysLate,
            reason: self::REASON_LOCKER_OVERDUE,
            lockerAssignmentId: $assignment->id,
        );
    }

    /**
     * Create an overdue fine safely and idempotently.
     *
     * This prevents duplicate fines when the same return
     * operation is processed more than once.
     */
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

        $query = Fine::query()
            ->where('reason', $reason);

        if ($borrowId !== null) {
            $query->where('borrow_id', $borrowId);
        }

        if ($bookingSeatId !== null) {
            $query->where('booking_seat_id', $bookingSeatId);
        }

        if ($lockerAssignmentId !== null) {
            $query->where(
                'locker_assignment_id',
                $lockerAssignmentId
            );
        }

        $existingFine = $query->first();

        if ($existingFine) {
            if (! $existingFine->invoice_id) {
                $this->linkFineToInvoice($existingFine);
            }

            return $existingFine->fresh();
        }

        return DB::transaction(function () use (
            $member,
            $amount,
            $reason,
            $borrowId,
            $bookingSeatId,
            $lockerAssignmentId,
            $daysLate
        ) {
            $fine = Fine::create([
                'member_id' => $member->id,

                'borrow_id' => $borrowId,

                'booking_seat_id' => $bookingSeatId,

                'locker_assignment_id' => $lockerAssignmentId,

                'amount' => round($amount, 2),

                'reason' => $reason,

                'issued_date' => Carbon::now()->toDateString(),

                'status' => 'unpaid',

                'days_late' => $daysLate,
            ]);

            $this->linkFineToInvoice($fine);

            return $fine->fresh();
        });
    }

    /**
     * Find or create a fine-type invoice for a member and link the fine.
     */
    protected function linkFineToInvoice(Fine $fine): void
    {
        $invoice = Invoice::query()
            ->where('member_id', $fine->member_id)
            ->where('invoice_type', self::INVOICE_TYPE_FINE)
            ->where('status', '!=', 'paid')
            ->first();

        if (! $invoice) {
            $invoice = Invoice::create([
                'member_id' => $fine->member_id,
                'invoice_number' => $this->generateFineInvoiceNumber(),
                'invoice_type' => self::INVOICE_TYPE_FINE,
                'total_amount' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'status' => 'unpaid',
                'due_date' => null,
                'notes' => 'Fine invoice',
            ]);
        }

        $fine->update(['invoice_id' => $invoice->id]);

        $this->syncFineInvoiceAmounts($invoice);
    }

    /**
     * Recalculate invoice totals from its fines.
     */
    protected function syncFineInvoiceAmounts(Invoice $invoice): void
    {
        $total = (float) $invoice->fines()
            ->where('status', '!=', 'waived')
            ->sum('amount');

        $invoice->update([
            'total_amount' => round($total, 2),
            'remaining_amount' => round(max(0, $total - (float) $invoice->paid_amount), 2),
        ]);
    }

    /**
     * Generate a unique invoice number for fines.
     */
    protected function generateFineInvoiceNumber(): string
    {
        $last = Invoice::where('invoice_type', self::INVOICE_TYPE_FINE)
            ->orderByDesc('id')
            ->first();

        $next = $last ? ((int) $last->id + 1) : 1;

        return 'FIN-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Sync fine status when its invoice is paid.
     */
    public function syncFineStatusOnInvoicePaid(Invoice $invoice): void
    {
        $invoice->loadMissing('fines');

        $fines = $invoice->fines()
            ->where('status', 'unpaid')
            ->get();

        if ($fines->isEmpty()) {
            return;
        }

        $totalFine = (float) $fines->sum('amount');
        $paidAmount = (float) $invoice->paid_amount;

        if ($paidAmount >= $totalFine) {
            $fines->each(function (Fine $fine) {
                $fine->update([
                    'status' => 'paid',
                    'paid_date' => Carbon::now()->toDateString(),
                ]);
            });
        } else {
            $ratio = $totalFine > 0 ? $paidAmount / $totalFine : 0;

            $fines->each(function (Fine $fine) use ($ratio) {
                $paidForFine = round((float) $fine->amount * $ratio, 2);

                if ($paidForFine >= (float) $fine->amount) {
                    $fine->update([
                        'status' => 'paid',
                        'paid_date' => Carbon::now()->toDateString(),
                    ]);
                } elseif ($paidForFine > 0) {
                    $fine->update(['status' => 'partially_paid']);
                }
            });
        }
    }

    /**
     * Get the member's current package.
     */
    protected function package(Member $member): ?Package
    {
        return $member->package;
    }

    /**
     * Calculate book overdue fine.
     */
    protected function bookOverdueAmount(
        ?Member $member,
        int $daysLate
    ): float {
        $perDay = 5.00;

        $package = $member
            ? $this->package($member)
            : null;

        if ($package?->overdue_fine_per_day !== null) {
            $perDay = (float) $package->overdue_fine_per_day;
        }

        return round(
            $perDay * $daysLate,
            2
        );
    }

    /**
     * Calculate seat overdue fine.
     */
    protected function seatOverdueAmount(
        ?Member $member,
        int $hoursLate
    ): float {
        $perHour = 2.00;

        $package = $member
            ? $this->package($member)
            : null;

        if ($package?->seat_overdue_fine_per_hour !== null) {
            $perHour = (float) $package->seat_overdue_fine_per_hour;
        }

        return round(
            $perHour * $hoursLate,
            2
        );
    }

    /**
     * Calculate locker overdue fine.
     */
    protected function lockerOverdueAmount(
        ?Member $member,
        int $daysLate
    ): float {
        $perDay = 3.00;

        $package = $member
            ? $this->package($member)
            : null;

        if ($package?->locker_overdue_fine_per_day !== null) {
            $perDay = (float) $package->locker_overdue_fine_per_day;
        }

        return round(
            $perDay * $daysLate,
            2
        );
    }
}