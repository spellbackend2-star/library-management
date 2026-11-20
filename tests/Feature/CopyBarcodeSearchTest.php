<?php

namespace Tests\Feature;

use App\Models\Copy;
use App\Repositories\Eloquent\CopyRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CopyBarcodeSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['borrows', 'copies', 'book_editions', 'books', 'members', 'staff'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('books', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
        });

        Schema::create('book_editions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('book_id');
        });

        Schema::create('copies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('edition_id');
            $table->string('barcode');
            $table->string('status');
            $table->date('acquisition_date')->nullable();
            $table->timestamps();
        });

        Schema::create('borrows', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('copy_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('staff_borrower_id')->nullable();
            $table->string('status');
        });

        Schema::create('members', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
        });

        Schema::create('staff', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
        });
    }

    public function test_copy_list_can_filter_by_exact_barcode(): void
    {
        $this->createBookWithCopy('LIB-1001');
        $this->createBookWithCopy('LIB-1002');

        $result = (new CopyRepository)->getAll([
            'barcode' => 'LIB-1002',
        ]);

        $this->assertCount(1, $result['data']);
        $this->assertSame('LIB-1002', $result['data'][0]->barcode);
    }

    public function test_book_copy_list_can_filter_by_barcode_without_leaking_other_books(): void
    {
        $bookId = $this->createBookWithCopy('LIB-2001');
        $this->createBookWithCopy('LIB-2002');

        $result = (new CopyRepository)->getForBook($bookId, [
            'barcode' => 'LIB-2002',
        ]);

        $this->assertCount(0, $result['data']);
    }

    private function createBookWithCopy(string $barcode): int
    {
        $bookId = DB::table('books')->insertGetId(['title' => 'Test Book']);
        $editionId = DB::table('book_editions')->insertGetId([
            'book_id' => $bookId,
        ]);

        Copy::create([
            'edition_id' => $editionId,
            'barcode' => $barcode,
            'status' => 'available',
        ]);

        return $bookId;
    }
}
