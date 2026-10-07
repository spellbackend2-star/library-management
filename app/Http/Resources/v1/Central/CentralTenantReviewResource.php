<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralTenantReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'phone' => $this->phone,
            'tenant_code' => $this->tenant_code,
            'status' => $this->status,
            'suspension_reason' => $this->suspension_reason,
            'domain' => $this->domains->first()?->domain,
        ];
    }
}