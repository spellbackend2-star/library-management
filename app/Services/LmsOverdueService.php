<?php

namespace App\Services;

use App\Models\BookingSeat;
use App\Models\Borrow;
use App\Models\LockerAssignment;
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
            'fines_created' => 0,
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
            'fines_created' => $this->createOverdueFines(),
            'seat_bookings_expired' => $this->expireSeatBookings(),
            'locker_assignments_expired' => $this->expireLockerAssignments(),
        ];
    }

    /**
     * Mark active borrows as overdue when their due date has passed.
     *
     * No fine is created here.
     * The final fine is calculated when the book is returned.
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
     * Mark expired seat bookings as completed.
     *
     * Fine calculation is handled separately by FineService
     * when the seat booking is actually completed/released.
     */
    protected function expireSeatBookings(): int
    {
        $now = Carbon::now();

        return BookingSeat::query()
            ->whereIn('status', ['booked', 'active'])
            ->whereNotNull('end_at')
            ->where('end_at', '<', $now)
            ->update([
                'status' => 'completed',
                'updated_at' => $now,
            ]);
    }

    /**
     * Mark active locker assignments as expired.
     *
     * No fine is created here.
     * FineService calculates the final locker overdue fine
     * when the locker is actually returned.
     */
    protected function expireLockerAssignments(): int
    {
        $today = Carbon::today()->toDateString();

        return DB::transaction(function () use ($today) {
            return LockerAssignment::query()
                ->where('status', 'active')
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', $today)
                ->update([
                    'status' => 'expired',
                    'updated_at' => Carbon::now(),
                ]);
        });
    }

    /**
     * Create overdue fines for borrows, seat bookings, and locker assignments
     * that are past their due date and do not yet have a fine.
     */
    protected function createOverdueFines(): int
    {
        $created = 0;

        $fineService = app(FineService::class);

        // Book borrows that are overdue and not yet returned
        $overdueBorrows = Borrow::query()
            ->where('status', 'overdue')
            ->whereNull('return_date')
            ->with('member')
            ->get();

        foreach ($overdueBorrows as $borrow) {
            $fine = $fineService->fineForBorrowOnReturn($borrow);

            if ($fine) {
                $created++;
            }
        }

        // Seat bookings that expired and are now completed
        $expiredSeats = BookingSeat::query()
            ->where('status', 'completed')
            ->with('member')
            ->get();

        foreach ($expiredSeats as $seat) {
            $fine = $fineService->fineForBookingSeatOnComplete($seat);

            if ($fine) {
                $created++;
            }
        }

        // Locker assignments that expired and were returned
        $expiredLockers = LockerAssignment::query()
            ->where('status', 'expired')
            ->with('member')
            ->get();

        foreach ($expiredLockers as $assignment) {
            $fine = $fineService->fineForLockerOnReturn($assignment);

            if ($fine) {
                $created++;
            }
        }

        return $created;
    }
}