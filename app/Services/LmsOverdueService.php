<?php

namespace App\Services;

use App\Models\BookingSeat;
use App\Models\Borrow;
use App\Models\Locker;
use App\Models\LockerAssignment;
use App\Models\Seat;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LmsOverdueService
{
    /**
     * Run all overdue checks across all tenants.
     *
     * @return array<string, int>
     */
    public function runAll(): array
    {
        $summary = [
            'tenants' => 0,
            'borrows_marked_overdue' => 0,
            'fines_updated' => 0,
            'seat_bookings_expired' => 0,
            'locker_assignments_expired' => 0,
        ];

        // Always get tenants from the central database.
        $tenants = Tenant::on('mysql')->get();

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $result = $this->runForCurrentTenant();

                foreach ($result as $key => $value) {
                    $summary[$key] += $value;
                }

                $summary['tenants']++;
            } catch (\Throwable $e) {
                Log::warning('LMS overdue check skipped tenant', [
                    'tenant' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                try {
                    if (tenancy()->initialized) {
                        tenancy()->end();
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to end tenancy after overdue check', [
                        'tenant' => $tenant->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $summary;
    }

    /**
     * Run overdue checks for the currently initialized tenant.
     *
     * @return array<string, int>
     */
    public function runForCurrentTenant(): array
    {
        return [
            'borrows_marked_overdue' => $this->processOverdueBorrows(),
            'fines_updated' => $this->accrueBookFines(),
            'seat_bookings_expired' => $this->expireSeatBookings(),
            'locker_assignments_expired' => $this->expireLockerAssignments(),
        ];
    }

    /**
     * Mark active borrows as overdue when their due date has passed.
     *
     * Fine accrual is based on whole days past due.
     */
    protected function processOverdueBorrows(): int
    {
        return DB::transaction(function () {
            $now = Carbon::now();

            return Borrow::query()
                ->where('status', 'active')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $now->toDateString())
                ->update([
                    'status' => 'overdue',
                    'updated_at' => $now,
                ]);
        });
    }

    /**
     * Create or update one book fine per overdue borrow using elapsed days.
     */
    protected function accrueBookFines(): int
    {
        $fineService = app(FineService::class);
        $updated = 0;

        Borrow::query()
            ->where('status', 'overdue')
            ->whereNull('return_date')
            ->with('member')
            ->orderBy('id')
            ->chunkById(100, function ($borrows) use ($fineService, &$updated) {
                foreach ($borrows as $borrow) {
                    if ($fineService->accrueBookFineForOverdueBorrow($borrow)) {
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    /**
     * Mark expired seat bookings as completed.
     *
     * Seat booking expiry does not create a fine.
     */
    protected function expireSeatBookings(): int
    {
        $now = Carbon::now();

        return DB::transaction(function () use ($now) {
            $expiredCount = BookingSeat::query()
                ->whereIn('status', ['booked', 'active'])
                ->whereNotNull('end_at')
                ->where('end_at', '<', $now)
                ->update([
                    'status' => 'completed',
                    'updated_at' => $now,
                ]);

            Seat::query()
                ->where('status', 'occupied')
                ->whereDoesntHave('bookingSeats', function ($bookingQuery) use ($now) {
                    $bookingQuery
                        ->whereIn('status', ['booked', 'active'])
                        ->where('end_at', '>', $now);
                })
                ->update(['status' => 'available']);

            return $expiredCount;
        });
    }

    /**
     * Mark active locker assignments as expired.
     *
     * Locker expiry does not create a fine.
     */
    protected function expireLockerAssignments(): int
    {
        $now = Carbon::now();

        return DB::transaction(function () use ($now) {
            $expiredCount = LockerAssignment::query()
                ->where('status', 'active')
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<', $now)
                ->update([
                    'status' => 'expired',
                    'updated_at' => $now,
                ]);

            Locker::query()
                ->where('status', 'assigned')
                ->whereDoesntHave('assignments', function ($assignmentQuery) use ($now) {
                    $assignmentQuery
                        ->where('status', 'active')
                        ->where('expiry_date', '>', $now);
                })
                ->update(['status' => 'available']);

            return $expiredCount;
        });
    }

}
