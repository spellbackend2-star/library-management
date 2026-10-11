<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\AssignCentralStaffRoleRequest;
use App\Http\Requests\Central\StoreCentralStaffRequest;
use App\Http\Requests\Central\UpdateCentralStaffRequest;
use App\Http\Resources\CentralStaffResource;
use App\Services\Interface\CentralStaffServiceInterface;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentralStaffController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected CentralStaffServiceInterface $centralStaffService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->centralStaffService->getAll(
            $request->only(['per_page', 'sort_by', 'sort_order'])
        );

        return $this->successResponse(
            CentralStaffResource::collection($result['data']),
            'Central staff retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function store(StoreCentralStaffRequest $request): JsonResponse
    {
        $staff = $this->centralStaffService->create($request->validated());

        return $this->successResponse(
            new CentralStaffResource($staff),
            'Central staff created successfully.',
            201
        );
    }

    public function show(int $staff): JsonResponse
    {
        $staffData = $this->centralStaffService->find($staff);

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
        $staffData = $this->centralStaffService->update(
            $staff,
            $request->validated()
        );

        return $this->successResponse(
            new CentralStaffResource($staffData),
            'Central staff updated successfully.'
        );
    }

    public function destroy(int $staff): JsonResponse
    {
        $this->centralStaffService->delete($staff);

        return $this->successResponse(
            null,
            'Central staff deleted successfully.'
        );
    }

    public function assignRole(
        AssignCentralStaffRoleRequest $request,
        int $staff
    ): JsonResponse {
        $staffData = $this->centralStaffService->assignRole(
            $staff,
            $request->validated('role')
        );

        return $this->successResponse(
            new CentralStaffResource($staffData),
            'Central staff role assigned successfully.'
        );
    }
}
