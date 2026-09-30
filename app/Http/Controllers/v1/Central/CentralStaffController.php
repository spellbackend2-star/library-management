<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\AssignCentralStaffRoleRequest;
use App\Http\Requests\Central\StoreCentralStaffRequest;
use App\Http\Requests\Central\UpdateCentralStaffRequest;
use App\Http\Resources\CentralStaffResource;
use App\Models\User;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CentralStaffController extends Controller
{
    use ResponseMessage;

    public function index(): JsonResponse
    {
        $staff = User::with(['roles', 'permissions'])
            ->latest('id')
            ->get();

        return $this->successResponse(
            CentralStaffResource::collection($staff),
            'Central staff retrieved successfully.'
        );
    }

    public function store(StoreCentralStaffRequest $request): JsonResponse
    {
        $staff = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
            ]);

            $roleName = $request->validated('role');
            $role = Role::where('name', $roleName)
                ->where('guard_name', 'api')
                ->first();

            if ($role) {
                $user->assignRole($role);
            }

            return $user->load(['roles', 'permissions']);
        });

        return $this->successResponse(
            new CentralStaffResource($staff),
            'Central staff created successfully.',
            201
        );
    }

    public function show(int $staff): JsonResponse
    {
        $staffData = User::with(['roles', 'permissions'])
            ->find($staff);

        abort_if(
            ! $staffData,
            404,
            'Central staff not found.'
        );

        return $this->successResponse(
            new CentralStaffResource($staffData),
            'Central staff retrieved successfully.'
        );
    }

    public function update(
        UpdateCentralStaffRequest $request,
        int $staff
    ): JsonResponse {
        $staffData = User::with(['roles', 'permissions'])
            ->findOrFail($staff);

        $staffData = DB::transaction(function () use ($request, $staffData) {
            $validated = $request->validated();
            $role = $validated['role'] ?? null;
            unset($validated['role']);

            $staffData->update($validated);

            if ($role) {
                $staffData->syncRoles([$role]);
            }

            return $staffData->fresh(['roles', 'permissions']);
        });

        return $this->successResponse(
            new CentralStaffResource($staffData),
            'Central staff updated successfully.'
        );
    }

    public function destroy(int $staff): JsonResponse
    {
        $staffData = User::findOrFail($staff);

        DB::transaction(function () use ($staffData) {
            $staffData->delete();
        });

        return $this->successResponse(
            null,
            'Central staff deleted successfully.'
        );
    }

    public function assignRole(
        AssignCentralStaffRoleRequest $request,
        int $staff
    ): JsonResponse {
        $staffData = User::with(['roles', 'permissions'])
            ->findOrFail($staff);

        $role = Role::where('name', $request->validated('role'))
            ->where('guard_name', 'api')
            ->firstOrFail();

        $staffData->syncRoles([$role->name]);

        return $this->successResponse(
            new CentralStaffResource($staffData->fresh(['roles', 'permissions'])),
            'Central staff role assigned successfully.'
        );
    }
}
