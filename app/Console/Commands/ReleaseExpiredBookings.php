<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ReleaseExpiredBookings extends Command
{
    protected $signature = 'bookings:release-expired';

    protected $description = 'Release seats and lockers for bookings pending payment for more than 5 minutes';

    public function handle(): int
    {
        $expiredAt = Carbon::now()->subMinutes(5);

        $expiredBookings = Booking::where('status', 'PENDING')
            ->where('payment_status', 'UNPAID')
            ->where('created_at', '<', $expiredAt)
            ->get();

        if ($expiredBookings->isEmpty()) {
            $this->info('No expired bookings found.');
            return self::SUCCESS;
        }

        $count = 0;

        foreach ($expiredBookings as $booking) {
            DB::transaction(function () use ($booking) {
                $booking->update([
                    'status' => 'CANCELLED',
                    'cancelled_at' => Carbon::now(),
                ]);

                $booking->bookingSeats()->where('status', 'booked')->update([
                    'status' => 'cancelled',
                ]);

                $booking->lockerAssignments()->where('status', 'active')->update([
                    'status' => 'cancelled',
                ]);
            });

            $count++;
        }

        $this->info("Released {$count} expired booking(s).");
        return self::SUCCESS;
    }
}
