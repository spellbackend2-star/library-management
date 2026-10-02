<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralLoginResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];
        $tenant = $this->resource['tenant'];
        $domain = $tenant?->domains()->first();

        $response = [
            'success' => true,
            'message' => 'Login successful',
            'token' => $this->resource['token'],
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
            $response['tenant'] = [
                'id' => $tenant->id,
                'company_name' => $tenant->company_name,
                'tenant_code' => $tenant->tenant_code,
                'domain' => $domain?->domain,
            ];
        }

        return $response;
    }
}