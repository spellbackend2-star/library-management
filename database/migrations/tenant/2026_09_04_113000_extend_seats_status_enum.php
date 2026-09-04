<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE seats DROP CHECK seats_status_check");
        DB::statement("ALTER TABLE seats MODIFY COLUMN status ENUM('available','occupied','maintenance','out_of_service') NOT NULL DEFAULT 'available'");
        DB::statement("
            ALTER TABLE seats
            ADD CONSTRAINT seats_status_check
            CHECK (
                status IN ('available', 'occupied', 'maintenance', 'out_of_service')
            )
        ");
    }

    public function down(): void
    {
        DB::statement("UPDATE seats SET status='available' WHERE status='occupied'");
        DB::statement("ALTER TABLE seats DROP CHECK seats_status_check");
        DB::statement("ALTER TABLE seats MODIFY COLUMN status ENUM('available','maintenance','out_of_service') NOT NULL DEFAULT 'available'");
        DB::statement("
            ALTER TABLE seats
            ADD CONSTRAINT seats_status_check
            CHECK (
                status IN ('available', 'maintenance', 'out_of_service')
            )
        ");
    }
};
