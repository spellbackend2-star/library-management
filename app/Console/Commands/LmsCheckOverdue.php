<?php

namespace App\Console\Commands;

use App\Services\LmsOverdueService;
use Illuminate\Console\Command;

class LmsCheckOverdue extends Command
{
    protected $signature = 'lms:check-overdue';

    protected $description = 'Mark overdue book borrows, accrue book fines, and expire seat bookings and lockers';

    public function handle(LmsOverdueService $service): int
    {
        $summary = $service->runAll();

        $this->info(sprintf(
            'LMS overdue check done. Tenants=%d borrows_marked_overdue=%d fines_updated=%d seat_bookings_expired=%d locker_assignments_expired=%d',
            $summary['tenants'],
            $summary['borrows_marked_overdue'],
            $summary['fines_updated'],
            $summary['seat_bookings_expired'],
            $summary['locker_assignments_expired'],
        ));

        return self::SUCCESS;
    }
}
