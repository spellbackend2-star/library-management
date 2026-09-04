<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->decimal('overdue_fine_per_day', 8, 2)
                ->default(5.00)
                ->after('max_locker_hours_per_day');

            $table->decimal('seat_overdue_fine_per_hour', 8, 2)
                ->default(2.00)
                ->after('overdue_fine_per_day');

            $table->decimal('locker_overdue_fine_per_day', 8, 2)
                ->default(3.00)
                ->after('seat_overdue_fine_per_hour');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'overdue_fine_per_day',
                'seat_overdue_fine_per_hour',
                'locker_overdue_fine_per_day',
            ]);
        });
    }
};
