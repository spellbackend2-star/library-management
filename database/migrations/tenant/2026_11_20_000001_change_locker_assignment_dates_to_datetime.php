<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locker_assignments', function (Blueprint $table) {
            $table->dateTime('assigned_date')->change();
            $table->dateTime('expiry_date')->change();
        });

        // Existing date-only assignments historically expired at the end of the date.
        DB::table('locker_assignments')
            ->select(['id', 'expiry_date'])
            ->orderBy('id')
            ->chunkById(500, function ($assignments) {
                foreach ($assignments as $assignment) {
                    DB::table('locker_assignments')
                        ->where('id', $assignment->id)
                        ->update([
                            'expiry_date' => Carbon::parse($assignment->expiry_date)
                                ->endOfDay()
                                ->format('Y-m-d H:i:s'),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('locker_assignments', function (Blueprint $table) {
            $table->date('assigned_date')->change();
            $table->date('expiry_date')->change();
        });
    }
};
