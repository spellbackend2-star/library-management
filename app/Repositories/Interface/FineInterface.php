<?php

namespace App\Repositories\Interface;

use App\Models\Borrow;
use App\Models\Fine;

interface FineInterface
{
    public function getAll(array $filters = []): array;

    public function find(int $id): ?Fine;

    public function findBorrow(int $borrowId): ?Borrow;
}
