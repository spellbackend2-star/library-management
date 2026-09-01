<?php

namespace App\Repositories\Interface;

interface PaymentRepositoryInterface
{
    public function getAll(array $filters = []);

    public function findById(int $id);

    public function create(array $data);

    public function existsByReference(string $reference): bool;
}
