<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->change();
            $table->foreignId('staff_id')->nullable()->after('member_id')
                ->constrained('staff')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('staff_id')->nullable()->after('member_id')
                ->constrained('staff')->nullOnDelete();
        });

        if (! Schema::hasTable('receipts')) {
            Schema::create('receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->unique()->constrained('payments')->cascadeOnDelete();
                $table->string('receipt_number')->unique();
                $table->decimal('amount', 12, 2);
                $table->dateTime('receipt_date');
                $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
                $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamps();
            });
        } else {
            Schema::table('receipts', function (Blueprint $table) {
                if (Schema::hasColumn('receipts', 'member_id')) {
                    $table->foreignId('member_id')->nullable()->change();
                }

                if (! Schema::hasColumn('receipts', 'staff_id')) {
                    $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_id');
            $table->foreignId('member_id')->nullable(false)->change();
        });
    }
};
