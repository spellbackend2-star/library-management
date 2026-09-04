<?php

namespace App\Console\Commands;

use App\Services\LmsOverdueService;
use Illuminate\Console\Command;

class LmsCheckOverdue extends Command
{
    protected $signature = 'lms:check-overdue';

    protected $description = 'Detect overdue seat bookings, book borrows, and locker assignments and create fines';

    public function handle(LmsOverdueService $service): int
    {
        $summary = $service->runAll();

        $this->info(sprintf(
            'LMS overdue check done. Tenants=%d borrows_marked_overdue=%d fines_created=%d seat_bookings_expired=%d locker_assignments_expired=%d',
            $summary['tenants'],
            $summary['borrows_marked_overdue'],
            $summary['fines_created'],
            $summary['seat_bookings_expired'],
            $summary['locker_assignments_expired'],
        ));

        return self::SUCCESS;
    }
}
