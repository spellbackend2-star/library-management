<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\IndexRoleRequest;
use App\Http\Requests\Tenant\StorePermissionRequest;
use App\Http\Requests\Tenant\StoreRoleRequest;
use App\Http\Requests\Tenant\UpdatePermissionRequest;
use App\Http\Requests\Tenant\UpdateRoleRequest;
use App\Http\Resources\v1\Tenant\PermissionResource;
use App\Http\Resources\v1\Tenant\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(IndexRoleRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = Role::query()->where('guard_name', 'api')->with('permissions:id,name,guard_name');
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) ($filters['per_page'] ?? 15);
        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Tenant roles retrieved successfully.',
            'data' => RoleResource::collection(collect($paginator->items()))->resolve(),
            'meta' => [
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'first_page_url' => $paginator->url(1),
                'last_page_url' => $paginator->url($paginator->lastPage()),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ],
        ]);
    }

    public function all(): JsonResponse
    {
        $roles = Role::query()->where('guard_name', 'api')->with('permissions:id,name,guard_name')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Tenant roles retrieved successfully.',
            'data' => RoleResource::collection($roles)->resolve(),
        ]);
    }

    public function show(int $role): JsonResponse
    {
        $tenantRole = Role::query()->where('guard_name', 'api')->with('permissions:id,name,guard_name')->findOrFail($role);

        return response()->json([
            'success' => true,
            'message' => 'Tenant role retrieved successfully.',
            'data' => (new RoleResource($tenantRole))->resolve(),
        ]);
    }

    public function permissions(): JsonResponse
    {
        $user = auth('api')->user();
        $permissions = Permission::query()->where('guard_name', 'api')->orderBy('name')->get()
            ->filter(fn (Permission $permission): bool => $user->can($permission->name))->values();

        return response()->json([
            'success' => true,
            'message' => 'Assignable tenant permissions retrieved successfully.',
            'data' => PermissionResource::collection($permissions)->resolve(),
        ]);
    }

    public function storePermission(StorePermissionRequest $request): JsonResponse
    {
        $permission = DB::transaction(function () use ($request): Permission {
            $permission = Permission::create(['name' => $request->validated('name'), 'guard_name' => 'api']);

            Role::query()->where('guard_name', 'api')->where('name', 'admin')->get()
                ->each(fn (Role $role) => $role->givePermissionTo($permission));

            return $permission;
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Tenant permission created successfully.',
            'data' => (new PermissionResource($permission))->resolve(),
        ], 201);
    }

    public function updatePermission(UpdatePermissionRequest $request, int $permission): JsonResponse
    {
        $tenantPermission = Permission::query()->where('guard_name', 'api')->findOrFail($permission);
        abort_if(str_starts_with($tenantPermission->name, 'roles.'), 403, 'Role management permissions cannot be modified.');

        $tenantPermission->update($request->validated());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Tenant permission updated successfully.',
            'data' => (new PermissionResource($tenantPermission->fresh()))->resolve(),
        ]);
    }

    public function destroyPermission(int $permission): JsonResponse
    {
        $tenantPermission = Permission::query()->where('guard_name', 'api')->findOrFail($permission);
        abort_if(str_starts_with($tenantPermission->name, 'roles.'), 403, 'Role management permissions cannot be deleted.');

        $tenantPermission->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json(['success' => true, 'message' => 'Tenant permission deleted successfully.']);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = DB::transaction(function () use ($data): Role {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'api']);
            $role->syncPermissions($data['permissions']);

            return $role;
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role->load('permissions:id,name,guard_name');

        return response()->json([
            'success' => true,
            'message' => 'Tenant role created successfully.',
            'data' => (new RoleResource($role))->resolve(),
        ], 201);
    }

    public function update(UpdateRoleRequest $request, int $role): JsonResponse
    {
        $tenantRole = Role::query()->where('guard_name', 'api')->with('permissions:id,name,guard_name')->findOrFail($role);
        abort_if($tenantRole->name === 'admin', 403, 'The admin role cannot be modified.');
        $data = $request->validated();

        DB::transaction(function () use ($tenantRole, $data): void {
            if (array_key_exists('name', $data)) {
                $tenantRole->name = $data['name'];
                $tenantRole->save();
            }
            if (array_key_exists('permissions', $data)) {
                $tenantRole->syncPermissions($data['permissions']);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $tenantRole->load('permissions:id,name,guard_name');

        return response()->json([
            'success' => true,
            'message' => 'Tenant role updated successfully.',
            'data' => (new RoleResource($tenantRole))->resolve(),
        ]);
    }

    public function destroy(int $role): JsonResponse
    {
        $tenantRole = Role::query()->where('guard_name', 'api')->findOrFail($role);
        abort_if($tenantRole->name === 'admin', 403, 'The admin role cannot be deleted.');

        $tenantRole->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json(['success' => true, 'message' => 'Tenant role deleted successfully.']);
    }
}
