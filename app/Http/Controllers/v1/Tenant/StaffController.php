<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\AssignRoleRequest;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Services\StaffService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected StaffService $staffService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->staffService->getAll($request->all());

        return $this->successResponse(
            StaffResource::collection($result['data']),
            'Staff retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $staff = $this->staffService->create(
            $request->validated()
        );

        return $this->successResponse(
            new StaffResource($staff),
            'Staff created successfully.',
            201
        );
    }

    public function show(int $staff): JsonResponse
    {
        $staffData = $this->staffService->getById($staff);

        abort_if(
            ! $staffData,
            404,
            'Staff not found.'
        );

        return $this->successResponse(
            new StaffResource($staffData),
            'Staff retrieved successfully.'
        );
    }

    public function update(
        UpdateStaffRequest $request,
        int $staff
    ): JsonResponse {
        $staffData = $this->staffService->update(
            $staff,
            $request->validated()
        );

        return $this->successResponse(
            new StaffResource($staffData),
            'Staff updated successfully.'
        );
    }

    public function destroy(int $staff): JsonResponse
    {
        $this->staffService->delete($staff);

        return $this->successResponse(
            null,
            'Staff deleted successfully.'
        );
    }

    public function activate(int $staff): JsonResponse
    {
        $staffData = $this->staffService->activate($staff);

        return $this->successResponse(
            new StaffResource($staffData),
            'Staff activated successfully.'
        );
    }

    public function deactivate(int $staff): JsonResponse
    {
        $staffData = $this->staffService->deactivate($staff);

        return $this->successResponse(
            new StaffResource($staffData),
            'Staff deactivated successfully.'
        );
    }

    public function assignRole(
        AssignRoleRequest $request,
        int $staff
    ): JsonResponse {
        $staffData = $this->staffService->assignRole(
            $staff,
            $request->validated('role')
        );

        return $this->successResponse(
            new StaffResource($staffData),
            'Staff role assigned successfully.'
        );
    }

    // public function setupOwner(): JsonResponse
    // {
    //     $staff = $this->staffService->setupOwner();

    //     return $this->successResponse(
    //         new StaffResource($staff),
    //         'Owner staff setup successfully.'
    //     );
    // }
}