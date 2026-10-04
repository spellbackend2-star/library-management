<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = Schema::connection($this->centralConnection());

        $schema->table('invoices', function (Blueprint $table) {
            $table->foreignId('coupon_id')
                ->nullable()
                ->after('discount')
                ->constrained('coupons')
                ->nullOnDelete();

            $table->decimal('coupon_discount', 12, 2)
                ->default(0)
                ->after('coupon_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = Schema::connection($this->centralConnection());

        $schema->table('invoices', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['coupon_id', 'coupon_discount']);
        });
    }

    /**
     * Central database connection name.
     */
    protected function centralConnection(): string
    {
        return config('tenancy.database.central_connection')
            ?? config('database.default');
    }
};