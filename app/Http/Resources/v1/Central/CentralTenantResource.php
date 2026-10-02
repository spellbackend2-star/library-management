<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralTenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tenant = $this->resource['tenant'];

        return [
            'id' => $tenant->id,
            'company_name' => $tenant->company_name,
            'tenant_code' => $tenant->tenant_code,
            'owner_email' => $tenant->owner_email,
            'owner_name' => $tenant->owner_name,
            'status' => $tenant->status,
            'domain' => $this->resource['domain'],
        ];
    }
}