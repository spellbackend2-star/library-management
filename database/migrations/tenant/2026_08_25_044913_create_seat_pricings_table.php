<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_pricing', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('seat_categories')
                ->cascadeOnDelete();

            $table->string('billing_period', 20);

            $table->decimal('price', 10, 2)
                ->default(0);

            $table->date('effective_from')
                ->useCurrent();

            $table->date('effective_to')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamp('created_at')
                ->useCurrent();
        });

        DB::statement("
            ALTER TABLE seat_pricing
            ADD CONSTRAINT seat_pricing_billing_period_check
            CHECK (
                billing_period IN ('hourly', 'daily', 'weekly', 'monthly')
            )
        ");

        DB::statement("
            ALTER TABLE seat_pricing
            ADD CONSTRAINT seat_pricing_price_check
            CHECK (price >= 0)
        ");

        DB::statement("
            ALTER TABLE seat_pricing
            ADD CONSTRAINT seat_pricing_effective_dates_check
            CHECK (
                effective_to IS NULL
                OR effective_to >= effective_from
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_pricing');
    }
};