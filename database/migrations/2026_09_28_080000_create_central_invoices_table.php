<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Central invoices table.
     *
     * This table always lives in the central database and is never
     * created inside a tenant database. It is completely separate from
     * the tenant "invoices" table so both can hold their own invoices
     * with their own formats and numbering.
     */
    public function up(): void
    {
        $schema = Schema::connection($this->centralConnection());

        if ($schema->hasTable('invoices')) {
            return;
        }

        $schema->create('invoices', function (Blueprint $table) {
            $table->id();

            $table->string('tenant_id', 36)->nullable();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();

            $table->foreignId('subscription_id')
                ->nullable()
                ->constrained('subscriptions')
                ->cascadeOnDelete();

            $table->foreignId('subscription_payment_id')
                ->nullable()
                ->constrained('subscription_payments')
                ->nullOnDelete();

            $table->string('invoice_number')->unique();

            $table->string('invoice_type', 50)->default('subscription');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);

            $table->string('currency', 10)->default('NPR');
            $table->string('currency_symbol', 20)->default('Rs.');

            $table->enum('status', [
                'unpaid',
                'partially_paid',
                'paid',
                'overdue',
                'cancelled',
                'refunded',
            ])->default('unpaid');

            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('subscription_id');
            $table->index('subscription_payment_id');
            $table->index('status');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->centralConnection())
            ->dropIfExists('invoices');
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
