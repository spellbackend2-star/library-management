<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->change();
            $table->foreignId('package_id')->nullable()->change();
            $table->foreignId('staff_borrower_id')->nullable()->after('member_id')
                ->constrained('staff')->nullOnDelete();
        });

        Schema::table('borrows', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->change();
            $table->foreignId('staff_borrower_id')->nullable()->after('staff_id')
                ->constrained('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_borrower_id');
            $table->foreignId('member_id')->nullable(false)->change();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_borrower_id');
            $table->foreignId('package_id')->nullable(false)->change();
            $table->foreignId('member_id')->nullable(false)->change();
        });
    }
};
