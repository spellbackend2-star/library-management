<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->dropUnique('packages_name_unique');
            $table->index('name');
            $table->date('retired_at')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->dropIndex(['name']);
            $table->dropColumn('retired_at');
            $table->dropSoftDeletes();
            $table->unique('name');
        });
    }
};
