<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['isbn_10', 'isbn_13'] as $column) {
            if (! Schema::hasColumn('book_editions', $column)) {
                continue;
            }

            Schema::table('book_editions', function (Blueprint $table) use ($column) {
                $table->dropUnique("book_editions_{$column}_unique");
                $table->dropColumn($column);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('book_editions', 'isbn_10')) {
            Schema::table('book_editions', function (Blueprint $table) {
                $table->string('isbn_10', 10)->nullable()->unique('book_editions_isbn_10_unique');
            });
        }

        if (! Schema::hasColumn('book_editions', 'isbn_13')) {
            Schema::table('book_editions', function (Blueprint $table) {
                $table->string('isbn_13', 13)->nullable()->unique('book_editions_isbn_13_unique');
            });
        }
    }
};
