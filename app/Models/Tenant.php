<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'pending', 'expired', 'cancelled'])
            ->latestOfMany();
    }

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
