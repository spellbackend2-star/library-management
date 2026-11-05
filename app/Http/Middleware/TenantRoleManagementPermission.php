<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantRoleManagementPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user('api');

        if (! $user || (! $user->hasRole('admin', 'api') && ! $user->can($permission))) {
            throw new AuthorizationException();
        }

        return $next($request);
    }
}
