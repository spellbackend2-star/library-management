<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('tenants', 'status')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->enum('status', ['inactive', 'active', 'suspended'])->default('inactive')->after('owner_email');
            });
        }

        DB::table('tenants')
            ->whereNull('status')
            ->orWhere('status', '')
            ->update(['status' => 'inactive']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'status')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
