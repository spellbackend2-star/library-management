<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantStaffDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->date('hire_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
    }

    public function test_deleting_staff_also_deletes_linked_user(): void
    {
        $user = User::create([
            'name' => 'Test Staff',
            'email' => 'staff-delete@example.test',
            'password' => 'password',
        ]);
        $staff = Staff::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Staff',
            'email' => $user->email,
        ]);

        $deleted = app(StaffService::class)->delete($staff->id);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('staff', ['id' => $staff->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}