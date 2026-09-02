<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('copies', function (Blueprint $table) {
            $table->dropUnique('copies_barcode_unique');
            $table->index('barcode', 'copies_barcode_index');
        });

        Schema::table('book_editions', function (Blueprint $table) {
            $table->dropUnique('book_editions_isbn_unique');
            $table->index('isbn', 'book_editions_isbn_index');
        });
    }

    public function down(): void
    {
        Schema::table('book_editions', function (Blueprint $table) {
            $table->dropIndex('book_editions_isbn_index');
            $table->unique('isbn', 'book_editions_isbn_unique');
        });

        Schema::table('copies', function (Blueprint $table) {
            $table->dropIndex('copies_barcode_index');
            $table->unique('barcode', 'copies_barcode_unique');
        });
    }
};
