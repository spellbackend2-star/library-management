<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            // Package
            $table->foreignId('package_id')
                ->constrained('packages')
                ->restrictOnDelete();

            // Personal Information
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)
                ->unique();

            $table->string('phone', 30)
                ->nullable();

            $table->string('address', 255)
                ->nullable();

            $table->date('date_of_birth')
                ->nullable();

            // Membership
            $table->date('membership_start')
                ->useCurrent();

            $table->date('membership_expiry')
                ->nullable();

            $table->string('status', 20)
                ->default('active');

            $table->timestamps();

            // Indexes
            $table->index('package_id');
            $table->index('membership_expiry');
        });

        // Status CHECK constraint
        DB::statement("
            ALTER TABLE members
            ADD CONSTRAINT members_status_check
            CHECK (status IN ('active', 'suspended', 'expired', 'cancelled'))
        ");

        // Membership expiry CHECK constraint
        DB::statement("
            ALTER TABLE members
            ADD CONSTRAINT members_expiry_check
            CHECK (
                membership_expiry IS NULL
                OR membership_expiry >= membership_start
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
