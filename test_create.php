<?php

require __DIR__ . '/../library-management/vendor/autoload.php';

$app = require __DIR__ . '/../library-management/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenantModel = \Stancl\Tenancy\Database\Models\Tenant::find('7da8dd39-f189-4122-ad86-c605bb21f367');
tenancy()->initialize($tenantModel);

$request = Illuminate\Http\Request::create(
    '/api/books',
    'POST',
    [],
    [],
    [],
    ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
    json_encode([
        'title' => 'Clean Code',
        'subtitle' => 'A Handbook of Agile Software Craftsmanship',
        'language' => 'English',
        'description' => 'A book about writing clean software.',
        'author_ids' => [1, 2],
        'category_ids' => [3, 5],
        'editions' => [[
            'publisher_id' => 1,
            'isbn' => '9780132350884',
            'edition_number' => '1st',
            'publication_year' => 2008,
            'format' => 'physical',
            'copies' => [
                ['barcode' => 'LIB-0001', 'shelf_location' => 'A-01', 'condition' => 'new'],
                ['barcode' => 'LIB-0002', 'shelf_location' => 'A-01', 'condition' => 'good'],
            ],
        ]],
    ])
);

$formRequest = new App\Http\Requests\Book\StoreBookRequest();
$formRequest->initialize([], [], [], [], [], [], json_encode([
    'title' => 'Clean Code',
    'subtitle' => 'A Handbook of Agile Software Craftsmanship',
    'language' => 'English',
    'description' => 'A book about writing clean software.',
    'author_ids' => [1, 2],
    'category_ids' => [3, 5],
    'editions' => [[
        'publisher_id' => 1,
        'isbn' => '9780132350884',
        'edition_number' => '1st',
        'publication_year' => 2008,
        'format' => 'physical',
        'copies' => [
            ['barcode' => 'LIB-0001', 'shelf_location' => 'A-01', 'condition' => 'new'],
            ['barcode' => 'LIB-0002', 'shelf_location' => 'A-01', 'condition' => 'good'],
        ],
    ]],
]));

try {
    $svc = $app->make(App\Services\BookService::class);
    $book = $svc->createWithRelations($formRequest->validated());
    echo "OK: book " . $book->id . "\n";
} catch (\Throwable $e) {
    echo "ERR " . get_class($e) . ': ' . $e->getMessage() . "\n";
    if (method_exists($e, 'errors')) {
        print_r($e->errors());
    }
}
