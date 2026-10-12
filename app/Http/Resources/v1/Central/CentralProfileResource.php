<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];

        if ($this->resource['updated'] ?? false) {
            return [
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ];
        }

        $tenant = $this->resource['tenant'];

        $response = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()
                ->pluck('name')
                ->values(),
        ];

        if ($tenant) {
            $domain = $tenant->domains()->first();
            $response['tenant'] = [
                'id' => $tenant->id,
                'company_name' => $tenant->company_name,
                'phone' => $tenant->phone,
                'tenant_code' => $tenant->tenant_code,
                'domain' => $domain?->domain,
            ];
        }

        return $response;
    }
}
