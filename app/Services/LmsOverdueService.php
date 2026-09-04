<?php

namespace App\Services;

use App\Models\BookingSeat;
use App\Models\Borrow;
use App\Models\Fine;
use App\Models\LockerAssignment;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LmsOverdueService
{
    /**
     * Run all overdue checks across all tenants.
     *
     * Returns a summary array used by the command for logging.
     *
     * @return array<string, int>
     */
    public function runAll(): array
    {
        $summary = [
            'tenants' => 0,
            'borrows_marked_overdue' => 0,
            'fines_created' => 0,
            'seat_bookings_expired' => 0,
            'locker_assignments_expired' => 0,
        ];

        // Always read tenant list from the central connection, otherwise
        // once tenancy is initialized for the first tenant the model's
        // default connection points at the tenant DB and we see zero rows.
        $tenants = Tenant::on('mysql')->get();

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $result = $this->runForCurrentTenant();

                foreach ($result as $k => $v) {
                    $summary[$k] += $v;
                }
                $summary['tenants']++;
            } catch (\Throwable $e) {
                \Log::warning('LMS overdue skipped tenant', [
                    'tenant' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                try {
                    if (tenancy()->initialized) {
                        tenancy()->end();
                    }
                } catch (\Throwable $e) {
                    // FilesystemTenancyBootstrapper can warn on revert
                    // in local env; safe to ignore so the scheduler
                    // continues to the next tenant.
                }
            }
        }

        return $summary;
    }

    /**
     * Run overdue checks for the currently active tenant.
     *
     * Must be called inside `tenancy()->initialize($tenant)`.
     *
     * @return array<string, int>
     */
    public function runForCurrentTenant(): array
    {
        return [
            'borrows_marked_overdue' => $this->processOverdueBorrows(),
            'fines_created' => $this->createOverdueFines(),
            'seat_bookings_expired' => $this->expireSeatBookings(),
            'locker_assignments_expired' => $this->expireLockerAssignments(),
        ];
    }

    /**
     * Mark active borrows past their due_date as 'overdue'.
     */
    public function processOverdueBorrows(): int
    {
        return DB::transaction(function () {
            $now = Carbon::now();

            return Borrow::whereIn('status', ['active'])
                ->whereNotNull('due_date')
                ->where('due_date', '<', $now->toDateString())
                ->update([
                    'status' => 'overdue',
                    'updated_at' => $now,
                ]);
        });
    }

    /**
     * Create a fine for every overdue borrow that doesn't already have one.
     * Fine amount is taken from the member's package (or a default of 5.00).
     */
    public function createOverdueFines(): int
    {
        $now = Carbon::now();

        $overdue = Borrow::where('status', 'overdue')->get();

        $created = 0;

        foreach ($overdue as $borrow) {
            $exists = Fine::where('borrow_id', $borrow->id)->exists();

            if ($exists) {
                continue;
            }

            Fine::create([
                'borrow_id' => $borrow->id,
                'member_id' => $borrow->member_id,
                'amount' => $this->fineAmountForBorrow($borrow),
                'reason' => 'overdue',
                'issued_date' => $now->toDateString(),
                'status' => 'unpaid',
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * Cancel seat-bookings whose end_at has passed without being completed.
     */
    public function expireSeatBookings(): int
    {
        $now = Carbon::now();

        return BookingSeat::whereIn('status', ['booked', 'active'])
            ->whereNotNull('end_at')
            ->where('end_at', '<', $now)
            ->update([
                'status' => 'completed',
            ]);
    }

    /**
     * Mark locker assignments as 'expired' once their expiry_date is past.
     */
    public function expireLockerAssignments(): int
    {
        $now = Carbon::now()->toDateString();

        $expired = LockerAssignment::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', $now)
            ->get();

        if ($expired->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($expired, $now) {
            foreach ($expired as $assignment) {
                $assignment->update([
                    'status' => 'expired',
                ]);

                if (!Fine::where('locker_assignment_id', $assignment->id)->exists()) {
                    Fine::create([
                        'locker_assignment_id' => $assignment->id,
                        'member_id' => $assignment->member_id,
                        'amount' => 5.00,
                        'reason' => 'locker_overdue',
                        'issued_date' => $now,
                        'status' => 'unpaid',
                    ]);
                }
            }
        });

        return $expired->count();
    }

    /**
     * Resolve the fine amount for an overdue borrow.
     */
    protected function fineAmountForBorrow(Borrow $borrow): float
    {
        $member = $borrow->member;

        $perDay = 5.00;

        if ($member && $member->package && $member->package->overdue_fine_per_day) {
            $perDay = (float) $member->package->overdue_fine_per_day;
        }

        $days = max(1, Carbon::now()->diffInDays(Carbon::parse($borrow->due_date)));

        return round($perDay * $days, 2);
    }
}
