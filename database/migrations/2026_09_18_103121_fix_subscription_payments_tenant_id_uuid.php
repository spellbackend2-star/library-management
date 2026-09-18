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
        $fkName = DB::selectOne("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscription_payments' AND COLUMN_NAME = 'tenant_id' AND CONSTRAINT_NAME <> 'PRIMARY' LIMIT 1");

        if ($fkName && isset($fkName->CONSTRAINT_NAME)) {
            DB::statement('ALTER TABLE subscription_payments DROP FOREIGN KEY `' . $fkName->CONSTRAINT_NAME . '`');
        }

        DB::statement('ALTER TABLE subscription_payments MODIFY tenant_id VARCHAR(36) NULL');

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $fkName = DB::selectOne("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscription_payments' AND COLUMN_NAME = 'tenant_id' AND CONSTRAINT_NAME <> 'PRIMARY' LIMIT 1");

        if ($fkName && isset($fkName->CONSTRAINT_NAME)) {
            DB::statement('ALTER TABLE subscription_payments DROP FOREIGN KEY `' . $fkName->CONSTRAINT_NAME . '`');
        }

        DB::statement('ALTER TABLE subscription_payments MODIFY tenant_id BIGINT UNSIGNED NULL');
    }
};
