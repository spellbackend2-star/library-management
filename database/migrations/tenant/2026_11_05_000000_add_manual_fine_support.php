<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fines', function (Blueprint $table) {
            $table->string('source_type', 20)->default('automatic')->after('locker_assignment_id');
            $table->string('manual_reason', 255)->nullable()->after('reason');
            $table->text('notes')->nullable()->after('manual_reason');
            $table->foreignId('created_by')->nullable()->after('member_id')
                ->constrained('users')->nullOnDelete();
            $table->enum('reason', [
                'overdue',
                'damaged',
                'lost',
                'locker_overdue',
                'seat_overdue',
                'manual',
            ])->change();
            $table->enum('status', [
                'unpaid',
                'partially_paid',
                'paid',
                'waived',
            ])->default('unpaid')->change();
        });

        DB::statement('ALTER TABLE fines DROP CHECK fine_source_check');
        DB::statement("ALTER TABLE fines ADD CONSTRAINT fine_source_check CHECK (((borrow_id IS NOT NULL) + (booking_seat_id IS NOT NULL) + (locker_assignment_id IS NOT NULL) = 1) OR (source_type = 'manual' AND borrow_id IS NULL AND booking_seat_id IS NULL AND locker_assignment_id IS NULL))");

        Schema::create('fine_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fine_id')->constrained('fines')->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', ['created', 'amount_changed', 'cancelled']);
            $table->decimal('old_amount', 10, 2)->nullable();
            $table->decimal('new_amount', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['fine_id', 'created_at']);
        });
    }

    public function down(): void
    {
        if (DB::table('fines')->where('source_type', 'manual')->exists()) {
            throw new \RuntimeException('Cannot remove manual fine support while manual fine records exist.');
        }

        if (DB::table('fines')->where('status', 'partially_paid')->exists()) {
            throw new \RuntimeException('Cannot remove partially paid fine status while such records exist.');
        }

        Schema::dropIfExists('fine_adjustments');

        DB::statement('ALTER TABLE fines DROP CHECK fine_source_check');
        DB::statement("ALTER TABLE fines ADD CONSTRAINT fine_source_check CHECK ((borrow_id IS NOT NULL) + (booking_seat_id IS NOT NULL) + (locker_assignment_id IS NOT NULL) = 1)");

        Schema::table('fines', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['source_type', 'manual_reason', 'notes', 'created_by']);
            $table->enum('reason', [
                'overdue',
                'damaged',
                'lost',
                'locker_overdue',
                'seat_overdue',
            ])->change();
            $table->enum('status', ['unpaid', 'paid', 'waived'])->default('unpaid')->change();
        });
    }
};
