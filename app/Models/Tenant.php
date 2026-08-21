<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $fillable = [
        'id',
        'company_name',
        'tenant_code',
        'passport_client_id',
        'passport_client_secret',
        'database',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'company_name',
            'tenant_code',
            'passport_client_id',
            'passport_client_secret',
            'database',
           
        ];
    }

}