<?php

namespace App\Repositories\Interface;

use App\Models\CentralInvoice;

interface CentralInvoiceInterface
{
    public function all();

    public function getAll(array $filters = []);

    public function find(int $id): ?CentralInvoice;

    public function findByNumber(string $invoiceNumber): ?CentralInvoice;

    public function create(array $data): CentralInvoice;

    public function update(int $id, array $data): CentralInvoice;

    public function delete(int $id): bool;
}
