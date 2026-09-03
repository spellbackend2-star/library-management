<?php

namespace App\Repositories\Interface;

use App\Models\Tenant;

interface TenantInterface
{
    public function findById(string $id): ?Tenant;

    public function findByOwnerEmail(string $email): ?Tenant;
}
