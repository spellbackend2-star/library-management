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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique();

            $table->enum('discount_type', [
                'FLAT',
                'PERCENT',
            ]);

            $table->decimal('discount_value', 8, 2);

            $table->decimal('max_discount', 8, 2)
                ->nullable();

            $table->decimal('min_order_value', 8, 2)
                ->default(0);

            $table->dateTime('valid_from')
                ->nullable();

            $table->dateTime('valid_until')
                ->nullable();

            $table->integer('max_uses')
                ->nullable();

            $table->integer('max_uses_per_user')
                ->default(1);

            $table->integer('used_count')
                ->default(0);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
