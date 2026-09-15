<?php

namespace App\Console\Commands;

use App\Models\Borrow;
use App\Models\Fine;
use App\Models\Tenant;
use App\Services\LmsOverdueService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DebugOverdueFineCommand extends Command
{
    protected $signature = 'lms:debug-overdue';
    protected $description = 'Diagnose createOverdueFines via reflection';

    public function handle(LmsOverdueService $service): int
    {
        $rc = new \ReflectionClass($service);
        $por = $rc->getMethod('processOverdueBorrows'); $por->setAccessible(true);
        $cof = $rc->getMethod('createOverdueFines');   $cof->setAccessible(true);

        foreach (Tenant::on('mysql')->get() as $tenant) {
            if ($tenant->id !== '31f15162-3f06-4027-b4b9-92305cc51f82') { continue; }
            $this->warn('--- tenant '.$tenant->id.' ---');
            try {
                tenancy()->initialize($tenant);
                $this->info('connection='.DB::connection()->getName());

                // ensure 2 active overdue borrows exist
                Borrow::firstOrCreate(['id'=>2], ['copy_id'=>6,'member_id'=>1,'due_date'=>Carbon::now()->subDays(2),'status'=>'active','checkout_date'=>now(),'created_at'=>now(),'updated_at'=>now()]);
                Borrow::firstOrCreate(['id'=>3], ['copy_id'=>7,'member_id'=>2,'due_date'=>Carbon::now()->subDays(3),'status'=>'active','checkout_id'=>null,'booking_id'=>null,'checkout_date'=>now(),'created_at'=>now(),'updated_at'=>now()]);
                Borrow::whereIn('id',[2,3])->update(['status'=>'active','due_date'=>Carbon::now()->subDays(2)]);
                $this->info('active count: '.Borrow::whereIn('id',[2,3])->count());

                $marked = $por->invoke($service);
                $this->info('processOverdueBorrows => '.$marked);
                $this->info('Borrow status overdue (Eloquent) => '.Borrow::where('status','overdue')->count());
                $this->info('Borrow status overdue (RAW) => '.DB::connection()->select("SELECT COUNT(*) c FROM borrows WHERE status='overdue'")[0]->c);

                try {
                    $created = $cof->invoke($service);
                    $this->info('createOverdueFines => '.$created);
                } catch (\Throwable $e) {
                    $this->error('createOverdueFines THREW: '.$e->getMessage());
                }
                $this->info('fines now for id 2,3 => '.Fine::whereIn('borrow_id',[2,3])->count());
            } catch (\Throwable $e) {
                $this->error('ERR: '.$e->getMessage());
            } finally {
                try { tenancy()->end(); } catch (\Throwable $e) {}
            }
        }
        return self::SUCCESS;
    }
}
