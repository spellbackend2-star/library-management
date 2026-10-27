<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Spatie\Permission\Models\Role;

class AssignableCentralRole implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = auth('api')->user();
        $role = Role::query()
            ->where('guard_name', 'api')
            ->where('name', $value)
            ->with('permissions')
            ->first();

        if (! $user || ! $role) {
            return;
        }

        if ($role->name === 'super_admin' && ! $user->hasRole('super_admin', 'api')) {
            $fail('Only a super admin can assign the super admin role.');

            return;
        }

        foreach ($role->permissions as $permission) {
            if (! $user->can($permission->name)) {
                $fail('You cannot assign a role with permissions you do not have.');

                return;
            }
        }
    }
}
