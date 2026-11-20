<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE members DROP CHECK members_status_check');
        DB::statement(<<<'SQL'
            ALTER TABLE members
            ADD CONSTRAINT members_status_check
            CHECK (status IN ('active', 'inactive', 'suspended', 'expired', 'cancelled'))
        SQL);
    }

    public function down(): void
    {
        DB::table('members')
            ->where('status', 'inactive')
            ->update(['status' => 'suspended']);

        DB::statement('ALTER TABLE members DROP CHECK members_status_check');
        DB::statement(<<<'SQL'
            ALTER TABLE members
            ADD CONSTRAINT members_status_check
            CHECK (status IN ('active', 'suspended', 'expired', 'cancelled'))
        SQL);
    }
};
