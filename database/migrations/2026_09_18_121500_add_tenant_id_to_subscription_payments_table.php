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
        if (!Schema::hasColumn('subscription_payments', 'tenant_id')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->string('tenant_id', 36)->nullable()->after('subscription_id');
            });

            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->nullOnDelete();
            });
        }

        DB::table('subscription_payments')
            ->whereNull('tenant_id')
            ->whereNotNull('subscription_id')
            ->lazyById()
            ->each(function ($payment) {
                $subscription = DB::table('subscriptions')
                    ->where('id', $payment->subscription_id)
                    ->first();

                if ($subscription && !empty($subscription->tenant_id)) {
                    DB::table('subscription_payments')
                        ->where('id', $payment->id)
                        ->update(['tenant_id' => $subscription->tenant_id]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('subscription_payments', 'tenant_id')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};
