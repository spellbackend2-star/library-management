<?php

namespace App\Services;

use App\Models\Borrow;
use App\Models\Fine;
use App\Models\FineAdjustment;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class FineService
{
    public const INVOICE_TYPE_FINE = 'fine';

    public const REASON_OVERDUE = 'overdue';

    public const REASON_DAMAGED = 'damaged';

    public const REASON_LOST = 'lost';

    public const REASON_MANUAL = 'manual';

    public function createManualFine(
        int $memberId,
        float $amount,
        string $reason,
        ?string $note,
        ?int $createdBy
    ): Fine {
        return DB::transaction(function () use ($memberId, $amount, $reason, $note, $createdBy) {
            $fine = Fine::create([
                'member_id' => $memberId,
                'created_by' => $createdBy,
                'source_type' => 'manual',
                'amount' => round($amount, 2),
                'reason' => self::REASON_MANUAL,
                'manual_reason' => $reason,
                'notes' => $note,
                'issued_date' => Carbon::now()->toDateString(),
                'status' => 'unpaid',
                'days_late' => 0,
            ]);

            FineAdjustment::create([
                'fine_id' => $fine->id,
                'actor_user_id' => $createdBy,
                'action' => 'created',
                'new_amount' => round($amount, 2),
                'note' => $note,
            ]);

            $this->linkFineToInvoice($fine);

            return $fine->fresh()->load(['member', 'invoice', 'adjustments']);
        });
    }

    public function updateFineAmount(
        int $fineId,
        float $amount,
        string $reason,
        ?string $notes,
        bool $notesProvided,
        ?int $actorUserId
    ): Fine {
        return DB::transaction(function () use ($fineId, $amount, $reason, $notes, $notesProvided, $actorUserId) {
            $fine = Fine::query()->lockForUpdate()->findOrFail($fineId);

            if ($fine->status !== 'unpaid') {
                throw new ConflictHttpException(
                    'Only unpaid fines with no payment history can be edited. Record a new charge as a separate manual fine.'
                );
            }

            $invoice = $fine->invoice_id
                ? Invoice::query()->lockForUpdate()->find($fine->invoice_id)
                : null;

            $oldAmount = (float) $fine->amount;
            $newAmount = round($amount, 2);

            $updates = [
                'amount' => $newAmount,
                'manual_reason' => $reason,
            ];
            if ($notesProvided) {
                $updates['notes'] = $notes;
            }
            $fine->update($updates);

            if ($oldAmount !== $newAmount) {
                FineAdjustment::create([
                    'fine_id' => $fine->id,
                    'actor_user_id' => $actorUserId,
                    'action' => 'amount_changed',
                    'old_amount' => $oldAmount,
                    'new_amount' => $newAmount,
                    'note' => $notes ?? ('Fine reason: '.$reason),
                ]);
            }

            if ($invoice) {
                $this->syncFineInvoiceAmounts($invoice);
            }

            return $fine->fresh()->load(['member', 'invoice', 'adjustments']);
        });
    }

    public function cancelFine(int $fineId, ?int $actorUserId, string $note = 'Fine cancelled by admin.'): Fine
    {
        return DB::transaction(function () use ($fineId, $actorUserId, $note) {
            $fine = Fine::query()->lockForUpdate()->findOrFail($fineId);

            if ($fine->status === 'waived') {
                return $fine->fresh()->load(['member', 'invoice', 'adjustments']);
            }

            if ($fine->status !== 'unpaid') {
                throw new ConflictHttpException('Only unpaid fines can be cancelled.');
            }

            $invoice = $fine->invoice_id
                ? Invoice::query()->lockForUpdate()->find($fine->invoice_id)
                : null;

            if ($invoice && ((float) $invoice->paid_amount > 0 || $invoice->status === 'paid')) {
                throw new ConflictHttpException('A fine linked to a paid or partially paid invoice cannot be cancelled.');
            }

            $fine->update(['status' => 'waived']);
            FineAdjustment::create([
                'fine_id' => $fine->id,
                'actor_user_id' => $actorUserId,
                'action' => 'cancelled',
                'old_amount' => $fine->amount,
                'new_amount' => 0,
                'note' => $note,
            ]);

            if ($invoice) {
                $this->syncFineInvoiceAmounts($invoice);
            }

            return $fine->fresh()->load(['member', 'invoice', 'adjustments']);
        });
    }

    /**
     * Permanently remove an unpaid fine and its audit adjustments.
     */
    public function deleteFine(int $fineId): bool
    {
        return DB::transaction(function () use ($fineId) {
            $fine = Fine::query()->lockForUpdate()->findOrFail($fineId);

            $invoice = $fine->invoice_id
                ? Invoice::query()->lockForUpdate()->find($fine->invoice_id)
                : null;

            if (in_array($fine->status, ['paid', 'partially_paid'], true)
                || ($invoice && ((float) $invoice->paid_amount > 0 || $invoice->status === 'paid'))) {
                throw new ConflictHttpException('A fine linked to a paid or partially paid invoice cannot be deleted.');
            }

            // Fine adjustments use a restrictive foreign key, so remove them explicitly.
            $fine->adjustments()->delete();
            $deleted = $fine->delete();

            if ($deleted && $invoice) {
                $this->syncFineInvoiceAmounts($invoice);
            }

            return (bool) $deleted;
        });
    }

    /**
     * Create an overdue fine when a book is returned.
     */
    public function fineForBorrowOnReturn(
        Borrow $borrow,
        ?string $returnDate = null
    ): ?Fine {
        if ($borrow->status !== 'returned' || !$borrow->due_date || !$borrow->member_id) {
            return null;
        }

        $actualReturn = Carbon::parse(
            $returnDate ?? $borrow->return_date ?? Carbon::now()->toDateString()
        )->startOfDay();
        $dueDate = Carbon::parse($borrow->due_date)->startOfDay();
        $daysLate = $this->daysLate($dueDate, $actualReturn);

        if ($daysLate <= 0) {
            return null;
        }

        $member = $borrow->member;
        $amount = $this->bookOverdueAmount($daysLate);

        return $this->createOverdueFine(
            member: $member,
            amount: $amount,
            daysLate: $daysLate,
            reason: self::REASON_OVERDUE,
            borrowId: $borrow->id,
        );
    }

    /**
     * Accrue the book fine daily while an overdue borrow remains unreturned.
     */
    public function accrueBookFineForOverdueBorrow(
        Borrow $borrow,
        ?Carbon $asOfDate = null
    ): ?Fine {
        if ($borrow->status !== 'overdue' || $borrow->return_date || !$borrow->due_date || !$borrow->member_id) {
            return null;
        }

        $dueDate = Carbon::parse($borrow->due_date)->startOfDay();
        $asOfDate = ($asOfDate ?? Carbon::today())->copy()->startOfDay();
        $daysLate = $this->daysLate($dueDate, $asOfDate);

        if ($daysLate <= 0) {
            return null;
        }

        $amount = $this->bookOverdueAmount($daysLate);
        $existingFine = Fine::query()
            ->where('borrow_id', $borrow->id)
            ->where('reason', self::REASON_OVERDUE)
            ->first();

        if ($existingFine?->status === 'waived') {
            return null;
        }

        if ($existingFine
            && (int) $existingFine->days_late === $daysLate
            && (float) $existingFine->amount === $amount) {
            return null;
        }

        return $this->createOverdueFine(
            member: $borrow->member,
            amount: $amount,
            daysLate: $daysLate,
            reason: self::REASON_OVERDUE,
            borrowId: $borrow->id,
        );
    }

    protected function daysLate(Carbon $dueDate, Carbon $comparisonDate): int
    {
        return max(0, (int) $dueDate->diffInDays($comparisonDate));
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
        int $borrowId,
    ): ?Fine {
        if (!$member) {
            return null;
        }

        return DB::transaction(function () use (
            $member,
            $amount,
            $reason,
            $borrowId,
            $daysLate
        ) {
            $fine = Fine::query()
                ->where('borrow_id', $borrowId)
                ->where('reason', $reason)
                ->lockForUpdate()
                ->first();

            if ($fine?->status === 'waived') {
                return $fine;
            }

            $newAmount = round($amount, 2);
            $created = !$fine;
            $changed = $created;

            if (!$fine) {
                $fine = Fine::create([
                    'member_id' => $member->id,
                    'borrow_id' => $borrowId,
                    'amount' => $newAmount,
                    'reason' => $reason,
                    'issued_date' => Carbon::now()->toDateString(),
                    'status' => 'unpaid',
                    'days_late' => $daysLate,
                ]);

                FineAdjustment::create([
                    'fine_id' => $fine->id,
                    'action' => 'created',
                    'new_amount' => $newAmount,
                    'note' => 'Book overdue fine accrued automatically.',
                ]);
            } elseif ((int) $fine->days_late !== $daysLate || (float) $fine->amount !== $newAmount) {
                $oldAmount = (float) $fine->amount;
                $fine->update([
                    'amount' => $newAmount,
                    'days_late' => $daysLate,
                ]);

                FineAdjustment::create([
                    'fine_id' => $fine->id,
                    'action' => 'amount_changed',
                    'old_amount' => $oldAmount,
                    'new_amount' => $newAmount,
                    'note' => 'Book overdue fine recalculated from actual late days.',
                ]);
                $changed = true;
            }

            if (!$fine->invoice_id) {
                $this->linkFineToInvoice($fine);
            } elseif ($changed) {
                $invoice = Invoice::query()->lockForUpdate()->find($fine->invoice_id);
                if ($invoice) {
                    $this->syncFineInvoiceAmounts($invoice);
                    $this->syncFineStatusesForInvoice($invoice);
                }
            }

            return $fine->fresh()->load(['invoice']);
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
            'status' => $total > 0 && (float) $invoice->paid_amount >= $total
                ? 'paid'
                : (((float) $invoice->paid_amount > 0) ? 'partially_paid' : 'unpaid'),
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
        $this->syncFineStatusesForInvoice($invoice);
    }

    protected function syncFineStatusesForInvoice(Invoice $invoice): void
    {
        $fines = $invoice->fines()->where('status', '!=', 'waived')->get();
        $totalFine = (float) $fines->sum('amount');
        $paidAmount = min((float) $invoice->paid_amount, $totalFine);
        $ratio = $totalFine > 0 ? $paidAmount / $totalFine : 0;

        foreach ($fines as $fine) {
            $paidForFine = round((float) $fine->amount * $ratio, 2);
            $status = $paidForFine >= (float) $fine->amount
                ? 'paid'
                : ($paidForFine > 0 ? 'partially_paid' : 'unpaid');

            $fine->update([
                'status' => $status,
                'paid_date' => $status === 'paid'
                    ? ($fine->paid_date ?? Carbon::now()->toDateString())
                    : null,
            ]);
        }
    }

    /**
     * Calculate book overdue fine.
     */
    protected function bookOverdueAmount(
        int $daysLate
    ): float {
        $perDay = (float) (Setting::query()
            ->where('group', 'fine')
            ->where('key', 'book_overdue_rate')
            ->value('value') ?? 5.00);

        return round(
            $perDay * $daysLate,
            2
        );
    }

}
