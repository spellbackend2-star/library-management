<?php

namespace App\Repositories\Interface;

use App\Models\Invoice;

interface InvoiceInterface
{
    public function all();

    public function find(int $id): ?Invoice;

    public function create(array $data): Invoice;

    public function update(int $id, array $data): Invoice;

    public function delete(int $id): bool;

    public function findByMember(int $memberId): ?Invoice;

    public function findByNumber(string $invoiceNumber): ?Invoice;
}
