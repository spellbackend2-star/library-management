<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Package;
use App\Services\MemberService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MemberDuplicateValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->unsignedInteger('duration');
            $table->string('duration_unit', 20)->default('month');
            $table->unsignedInteger('max_book_loans')->nullable();
            $table->unsignedInteger('max_borrow_days')->nullable();
            $table->boolean('seat_access_allowed')->default(true);
            $table->boolean('locker_allowed')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_id');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('address', 255)->nullable();
            $table->date('membership_start');
            $table->date('membership_expiry')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('gender', 20)->nullable();
            $table->timestamps();
        });
    }

    public function test_duplicate_member_email_throws_validation_exception(): void
    {
        Package::create([
            'name' => 'Basic',
            'description' => 'Basic package',
            'price' => 100.00,
            'duration' => 1,
            'duration_unit' => 'month',
            'max_book_loans' => 3,
            'max_borrow_days' => 14,
            'seat_access_allowed' => false,
            'locker_allowed' => false,
            'is_active' => true,
        ]);

        Member::create([
            'package_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'binita@gmail.com',
            'phone' => '9800000001',
            'address' => 'Kathmandu',
            'membership_start' => '2026-09-22',
            'gender' => 'male',
        ]);

        $service = app(MemberService::class);

        try {
            $service->create([
                'package_id' => 1,
                'first_name' => 'Joh2n',
                'last_name' => 'Do2e',
                'email' => 'binita@gmail.com',
                'phone' => '9800000002',
                'address' => 'Kathmandu',
                'membership_start' => '2026-09-23',
                'gender' => 'male',
            ]);

            $this->fail('Expected a duplicate email validation error.');
        } catch (ValidationException $e) {
            $this->assertSame('The email has already been taken.', $e->errors()['email'][0]);
        }
    }
}
