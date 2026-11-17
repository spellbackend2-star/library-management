<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fines', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->change();
            $table->foreignId('staff_id')->nullable()->after('member_id')
                ->constrained('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_id');
            $table->foreignId('member_id')->nullable(false)->change();
        });
    }
};
