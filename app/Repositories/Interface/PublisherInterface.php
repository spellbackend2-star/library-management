<?php

namespace App\Repositories\Interface;

use App\Models\Publisher;

interface PublisherInterface
{
    public function all();

    public function find(int $id): ?Publisher;

    public function create(array $data): Publisher;

    public function update(int $id, array $data): Publisher;

    public function delete(int $id): bool;
}
