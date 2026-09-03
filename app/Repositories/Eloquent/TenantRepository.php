<?php

namespace App\Repositories\Eloquent;

use App\Models\Tenant;
use App\Repositories\Interface\TenantInterface;

class TenantRepository implements TenantInterface
{
    public function findById(string $id): ?Tenant
    {
        return Tenant::find($id);
    }

    public function findByOwnerEmail(string $email): ?Tenant
    {
        return Tenant::where('owner_email', $email)->first();
    }
}
