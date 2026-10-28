<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Central\CentralRoleResource;
use App\Http\Requests\Central\IndexCentralRoleRequest;
use App\Http\Requests\Central\StoreCentralPermissionRequest;
use App\Http\Requests\Central\StoreCentralRoleRequest;
use App\Http\Requests\Central\UpdateCentralPermissionRequest;
use App\Http\Requests\Central\UpdateCentralRoleRequest;
use App\Http\Resources\v1\Central\CentralPermissionResource;
use App\Repositories\Interface\CentralRoleInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CentralRoleController extends Controller
{
    public function __construct(
        protected CentralRoleInterface $centralRoleRepository
    ) {}

    public function index(IndexCentralRoleRequest $request): JsonResponse
    {
        $result = $this->centralRoleRepository->getAll(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Central roles retrieved successfully.',
            'data' => CentralRoleResource::collection(collect($result['data']))->resolve(),
            'meta' => $result['meta'],
        ]);
    }

    public function all(): JsonResponse
    {
        $roles = $this->centralRoleRepository->getAllWithoutPagination();

        return response()->json([
            'success' => true,
            'message' => 'Central roles retrieved successfully.',
            'data' => CentralRoleResource::collection($roles)->resolve(),
        ]);
    }

    public function show(int $role): JsonResponse
    {
        $centralRole = $this->centralRoleRepository->findById($role);

        return response()->json([
            'success' => true,
            'message' => 'Central role retrieved successfully.',
            'data' => (new CentralRoleResource($centralRole))->resolve(),
        ]);
    }

    public function permissions(): JsonResponse
    {
        $user = auth('api')->user();
        $permissions = Permission::query()
            ->where('guard_name', 'api')
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name', 'created_at', 'updated_at'])
            ->filter(fn (Permission $permission): bool => $user->can($permission->name))
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Assignable central permissions retrieved successfully.',
            'data' => CentralPermissionResource::collection($permissions)->resolve(),
        ]);
    }

    public function storePermission(StoreCentralPermissionRequest $request): JsonResponse
    {
        $permission = DB::transaction(function () use ($request): Permission {
            $permission = Permission::create([
                'name' => $request->validated('name'),
                'guard_name' => 'api',
            ]);

            Role::query()
                ->where('guard_name', 'api')
                ->where('name', 'super_admin')
                ->get()
                ->each(fn (Role $role) => $role->givePermissionTo($permission));

            return $permission;
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Central permission created successfully.',
            'data' => (new CentralPermissionResource($permission))->resolve(),
        ], 201);
    }

    public function updatePermission(UpdateCentralPermissionRequest $request, int $permission): JsonResponse
    {
        $centralPermission = Permission::query()
            ->where('guard_name', 'api')
            ->findOrFail($permission);

        abort_if(str_starts_with($centralPermission->name, 'roles.'), 403, 'Role management permissions cannot be modified.');

        $centralPermission->update($request->validated());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Central permission updated successfully.',
            'data' => (new CentralPermissionResource($centralPermission->fresh()))->resolve(),
        ]);
    }

    public function destroyPermission(int $permission): JsonResponse
    {
        $centralPermission = Permission::query()
            ->where('guard_name', 'api')
            ->findOrFail($permission);

        abort_if(str_starts_with($centralPermission->name, 'roles.'), 403, 'Role management permissions cannot be deleted.');

        $centralPermission->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Central permission deleted successfully.',
        ]);
    }

    public function store(StoreCentralRoleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'api',
        ]);
        $role->syncPermissions($data['permissions']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role->load('permissions:id,name,guard_name');

        return response()->json([
            'success' => true,
            'message' => 'Central role created successfully.',
            'data' => (new CentralRoleResource($role))->resolve(),
        ], 201);
    }

    public function update(UpdateCentralRoleRequest $request, int $role): JsonResponse
    {
        $centralRole = $this->centralRoleRepository->findById($role);

        abort_if($centralRole->name === 'super_admin', 403, 'The super admin role cannot be modified.');

        $data = $request->validated();

        DB::transaction(function () use ($centralRole, $data): void {
            if (array_key_exists('name', $data)) {
                $centralRole->name = $data['name'];
                $centralRole->save();
            }

            if (array_key_exists('permissions', $data)) {
                $centralRole->syncPermissions($data['permissions']);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $centralRole->load('permissions:id,name,guard_name');

        return response()->json([
            'success' => true,
            'message' => 'Central role updated successfully.',
            'data' => (new CentralRoleResource($centralRole))->resolve(),
        ]);
    }

    public function destroy(int $role): JsonResponse
    {
        $centralRole = $this->centralRoleRepository->findById($role);

        abort_if($centralRole->name === 'super_admin', 403, 'The super admin role cannot be deleted.');

        $centralRole->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Central role deleted successfully.',
        ]);
    }
}
