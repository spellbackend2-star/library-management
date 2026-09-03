<?php

namespace App\Repositories\Eloquent;

use App\Models\Tenant;
use App\Repositories\Interface\TenantInterface;
use Stancl\Tenancy\Database\Models\Domain;

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

    public function findByDomain(string $domain): ?Tenant
    {
        $domainModel = Domain::where('domain', $domain)->first();

        return $domainModel?->tenant;
    }
}
