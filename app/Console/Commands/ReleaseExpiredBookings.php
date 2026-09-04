<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Tenant;
use App\Services\BookingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseExpiredBookings extends Command
{
    protected $signature = 'bookings:release-expired';

    protected $description = 'Release seats and lockers for bookings pending payment for more than 5 minutes';

    public function handle(BookingService $bookingService): int
    {
        $expiredAt = Carbon::now()->subMinutes(5);
        $released = 0;

        foreach (Tenant::on('mysql')->get() as $tenant) {
            try {
                $dbName = $tenant->database()->getName();
                $manager = $tenant->database()->manager();
                if (!$manager->databaseExists($dbName)) {
                    $this->warn("Skipping tenant {$tenant->id} ({$tenant->company_name}): database {$dbName} not found.");
                    continue;
                }
                tenancy()->initialize($tenant);

                $expiredBookings = Booking::where('status', 'PENDING')
                    ->where('payment_status', 'UNPAID')
                    ->where('created_at', '<', $expiredAt)
                    ->get();

                foreach ($expiredBookings as $booking) {
                    DB::transaction(function () use ($booking) {
                        $booking->update([
                            'status' => 'CANCELLED',
                            'cancelled_at' => Carbon::now(),
                        ]);

                        $booking->bookingSeats()
                            ->where('status', '!=', 'cancelled')
                            ->get()
                            ->each(function ($seat) {
                                $seat->update(['status' => 'cancelled']);
                                if ($seat->seat_id) {
                                    \App\Models\Seat::where('id', $seat->seat_id)
                                        ->update(['status' => 'available']);
                                }
                            });

                        $booking->lockerAssignments()
                            ->where('status', '!=', 'cancelled')
                            ->get()
                            ->each(function ($assignment) {
                                $assignment->update(['status' => 'cancelled']);
                                if ($assignment->locker_id) {
                                    \App\Models\Locker::where('id', $assignment->locker_id)
                                        ->update(['status' => 'available']);
                                }
                            });

                        $booking->borrows()
                            ->whereIn('status', ['active', 'overdue'])
                            ->get()
                            ->each(function ($borrow) {
                                if ($borrow->copy_id) {
                                    \App\Models\Copy::where('id', $borrow->copy_id)
                                        ->update(['status' => 'available']);
                                }
                                $borrow->delete();
                            });
                    });

                    $released++;
                }
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        $this->info("Released {$released} expired booking(s).");

        return self::SUCCESS;
    }
}
