<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Central settings table.
     *
     * This table always lives in the central database and is never
     * created inside a tenant database. The table is created only when
     * it is missing so existing installations are reused as they are.
     */
    public function up(): void
    {
        $schema = Schema::connection($this->centralConnection());

        if ($schema->hasTable('settings')) {
            return;
        }

        $schema->create('settings', function (Blueprint $table) {
            $table->id();

            $table->string('group', 50)->default('general');
            $table->string('key', 100);
            $table->text('value')->nullable();

            $table->string('type', 20)->default('string');
            $table->string('description', 255)->nullable();

            $table->boolean('is_locked')->default(false);

            $table->timestamps();

            $table->unique(['group', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->centralConnection())
            ->dropIfExists('settings');
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
