<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('members', 'members_email_unique')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropUnique('members_email_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};
