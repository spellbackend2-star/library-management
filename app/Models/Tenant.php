<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $fillable = [
        'id',
        'company_name',
        'tenant_code',
        'owner_email',
        'owner_name',
        'phone',
        'status',
        'suspension_reason',
        'passport_client_id',
        'passport_client_secret',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'company_name',
            'tenant_code',
            'owner_email',
            'owner_name',
            'phone',
            'status',
            'passport_client_id',
            'passport_client_secret',
        ];
    }
}
