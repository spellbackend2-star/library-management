<?php

namespace App\Http\Resources\v1\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Permission;

class TenantProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $permissions = $this->hasRole('admin', 'api')
            ? Permission::query()
                ->where('guard_name', 'api')
                ->pluck('name')
                ->values()
            : $this->getAllPermissions()
                ->pluck('name')
                ->values();

        return [
            'user' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
            ],
            'roles' => $this->getRoleNames()->values(),
            'permissions' => $permissions,
        ];
    }
}
