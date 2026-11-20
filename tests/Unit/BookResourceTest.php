<?php

namespace Tests\Unit;

use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\BookEdition;
use App\Models\Copy;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class BookResourceTest extends TestCase
{
    public function test_it_reports_copy_counts_across_all_editions(): void
    {
        $firstEdition = new BookEdition;
        $firstEdition->setRelation('copies', new EloquentCollection([
            new Copy(['status' => 'available']),
            new Copy(['status' => 'on_loan']),
        ]));

        $secondEdition = new BookEdition;
        $secondEdition->setRelation('copies', new EloquentCollection([
            new Copy(['status' => 'available']),
            new Copy(['status' => 'reserved']),
        ]));

        $book = new Book;
        $book->setRelation('editions', new EloquentCollection([
            $firstEdition,
            $secondEdition,
        ]));

        $resource = new BookResource($book);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame(4, $data['total_copies']);
        $this->assertSame(2, $data['available_copies']);
        $this->assertSame(2, $data['unavailable_copies']);
    }
}
