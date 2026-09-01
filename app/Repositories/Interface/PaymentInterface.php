<?php

namespace App\Repositories\Interface;

use App\Models\Payment;

interface PaymentInterface
{
    public function all();

    public function find(int $id): ?Payment;

    public function create(array $data): Payment;

    public function update(int $id, array $data): Payment;

    public function delete(int $id): bool;
}
