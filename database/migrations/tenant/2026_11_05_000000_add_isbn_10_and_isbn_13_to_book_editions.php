<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

        $seen = ['isbn_10' => [], 'isbn_13' => []];
        foreach (DB::table('book_editions')->orderBy('id')->get(['id', 'isbn', 'isbn_10', 'isbn_13']) as $edition) {
            $isbn = strtoupper((string) preg_replace('/[\s-]+/u', '', trim($edition->isbn)));
            $column = strlen($isbn) === 10 ? 'isbn_10' : (strlen($isbn) === 13 ? 'isbn_13' : null);

            if ($column !== null) {
                $isbn = $edition->{$column} ?: $isbn;
                if (isset($seen[$column][$isbn])) {
                    // Preserve duplicate legacy ISBNs in the old column for manual cleanup.
                    continue;
                }
                $seen[$column][$isbn] = true;

                if ($edition->{$column} === null) {
                DB::table('book_editions')->where('id', $edition->id)->update([$column => $isbn]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('book_editions', function (Blueprint $table) {
            $table->dropUnique('book_editions_isbn_10_unique');
            $table->dropUnique('book_editions_isbn_13_unique');
            $table->dropColumn(['isbn_10', 'isbn_13']);
        });
    }
};
