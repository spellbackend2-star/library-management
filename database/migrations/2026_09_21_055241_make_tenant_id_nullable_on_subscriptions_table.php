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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Fill NULL tenant_ids with a default or delete those rows first
        DB::table('subscriptions')
            ->whereNull('tenant_id')
            ->delete();

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable(false)->change();
        });
    }
};
