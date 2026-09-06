<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('remaining_amount', 12, 2)->default(0)->after('paid_amount');
        });

        \Illuminate\Support\Facades\DB::table('invoices')
            ->whereRaw('total_amount > paid_amount')
            ->update([
                'remaining_amount' => \Illuminate\Support\Facades\DB::raw('total_amount - paid_amount'),
            ]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('remaining_amount');
        });
    }
};
