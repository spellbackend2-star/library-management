<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $editions = DB::table('book_editions')->whereNotNull('isbn')->get(['id', 'isbn']);
        $isbnGroups = [];
        foreach ($editions as $edition) {
            $isbn = strtoupper((string) preg_replace('/[\s-]+/u', '', trim($edition->isbn)));
            if ($isbn !== '') {
                $isbnGroups[$isbn][] = $edition->id;
            }
        }

        $copies = DB::table('copies')->get(['id', 'barcode']);
        $barcodeGroups = [];
        foreach ($copies as $copy) {
            $barcode = strtolower(trim($copy->barcode));
            if ($barcode !== '') {
                $barcodeGroups[$barcode][] = $copy->id;
            }
        }

        $duplicateIsbns = array_filter($isbnGroups, fn (array $ids) => count($ids) > 1);
        $duplicateBarcodes = array_filter($barcodeGroups, fn (array $ids) => count($ids) > 1);
        if ($duplicateIsbns !== [] || $duplicateBarcodes !== []) {
            $details = [];
            if ($duplicateIsbns !== []) {
                $details[] = 'duplicate normalized ISBN edition IDs: '.json_encode(array_values($duplicateIsbns));
            }
            if ($duplicateBarcodes !== []) {
                $details[] = 'duplicate trimmed barcode copy IDs: '.json_encode(array_values($duplicateBarcodes));
            }

            throw new \RuntimeException(
                'Cannot restore ISBN/barcode uniqueness until duplicates are resolved ('.implode('; ', $details).').'
            );
        }

        foreach ($editions as $edition) {
            $normalized = strtoupper((string) preg_replace('/[\s-]+/u', '', trim($edition->isbn)));
            DB::table('book_editions')->where('id', $edition->id)
                ->update(['isbn' => $normalized === '' ? null : $normalized]);
        }
        foreach ($copies as $copy) {
            DB::table('copies')->where('id', $copy->id)
                ->update(['barcode' => trim($copy->barcode)]);
        }

        Schema::table('book_editions', function (Blueprint $table) {
            $table->dropIndex('book_editions_isbn_index');
            $table->unique('isbn', 'book_editions_isbn_unique');
        });
        Schema::table('copies', function (Blueprint $table) {
            $table->dropIndex('copies_barcode_index');
            $table->unique('barcode', 'copies_barcode_unique');
        });
    }

    public function down(): void
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
};
