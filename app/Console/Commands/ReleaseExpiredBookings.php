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
                            ->where('status', 'booked')
                            ->update(['status' => 'cancelled']);

                        $booking->lockerAssignments()
                            ->where('status', 'active')
                            ->update(['status' => 'cancelled']);

                        $borrows = $booking->borrows()
                            ->whereIn('status', ['active', 'overdue'])
                            ->get();

                        foreach ($borrows as $borrow) {
                            if ($borrow->copy_id) {
                                \App\Models\Copy::where('id', $borrow->copy_id)
                                    ->update(['status' => 'available']);
                            }
                            $borrow->delete();
                        }
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
