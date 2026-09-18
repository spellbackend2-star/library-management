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
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_id')
                ->constrained('subscriptions')
                ->cascadeOnDelete();

            $table->string('tenant_id', 36)->nullable();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();

            $table->decimal('amount', 12, 2);

            $table->enum('payment_method', [
                'CASH',
                'KHALTI',
                'ESEWA',
            ]);

            $table->enum('status', [
                'PENDING',
                'SUCCESS',
                'FAILED',
            ])->default('PENDING');

            $table->string('transaction_id')->nullable();

            $table->text('gateway_response')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
