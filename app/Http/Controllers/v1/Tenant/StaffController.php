<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\AssignRoleRequest;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Services\StaffService;
use Illuminate\Http\JsonResponse;

class StaffController extends Controller
{
    public function __construct(
        protected StaffService $staffService
    ) {}

    public function index()
    {
        return StaffResource::collection(
            $this->staffService->getAll()
        );
    }

    public function store(StoreStaffRequest $request): StaffResource|JsonResponse
    {
        try {
            $staff = $this->staffService->create(
                $request->validated()
            );

            return new StaffResource($staff);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function show(int $staff): StaffResource
    {
        $staffData = $this->staffService->getById($staff);

        abort_if(!$staffData, 404, 'Staff not found.');

        return new StaffResource($staffData);
    }

    public function update(
        UpdateStaffRequest $request,
        int $staff
    ): StaffResource {
        $staffData = $this->staffService->update(
            $staff,
            $request->validated()
        );

        return new StaffResource($staffData);
    }

    public function destroy(int $staff): JsonResponse
    {
        $this->staffService->delete($staff);

        return response()->json([
            'message' => 'Staff deleted successfully.',
        ]);
    }

    public function activate(int $staff): StaffResource
    {
        $staffData = $this->staffService->activate($staff);

        return new StaffResource($staffData);
    }

    public function deactivate(int $staff): StaffResource
    {
        $staffData = $this->staffService->deactivate($staff);

        return new StaffResource($staffData);
    }

    public function assignRole(
        AssignRoleRequest $request,
        int $staff
    ): StaffResource {
        $staffData = $this->staffService->assignRole(
            $staff,
            $request->validated('role')
        );

        return new StaffResource($staffData);
    }

    public function setupOwner(): StaffResource
    {
        $user = auth('api')->user();

        abort_if(!$user, 401, 'Unauthenticated.');

        $staff = $user->staff;

        if (!$staff) {
            $nameParts = explode(' ', $user->name, 2);

            $staff = $user->staff()->create([
                'first_name' => $nameParts[0] ?? $user->name,
                'last_name' => $nameParts[1] ?? '',
                'email' => $user->email,
            ]);
        }

        $user->syncRoles(['admin']);

        return new StaffResource($staff->fresh());
    }
}
