<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\LockerAssigments\StoreLockerAssigmentsRequest;
use App\Http\Requests\LockerAssigments\UpdateLockerAssigmentsRequest;
use App\Http\Resources\LockerAssigmentsResource;
use App\Services\LockerAssignmentService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LockerAssigmentsController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected LockerAssignmentService $lockerAssigmentsService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'booking_id',
            'locker_id',
            'member_id',
            'status',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $result = $this->lockerAssigmentsService->getAll($filters);

        return $this->successResponse(
            LockerAssigmentsResource::collection($result['data']),
            'Locker assignments retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function store(StoreLockerAssigmentsRequest $request): JsonResponse
    {
        $assignment = $this->lockerAssigmentsService->create(
            $request->validated()
        );

        return $this->successResponse(
            new LockerAssigmentsResource($assignment),
            'Locker assignment created successfully.',
            201
        );
    }

    public function show(int $locker_assigments): JsonResponse
    {
        $assignmentData = $this->lockerAssigmentsService->getById($locker_assigments);

        abort_if(!$assignmentData, 404, 'Locker assignment not found.');

        return $this->successResponse(
            new LockerAssigmentsResource($assignmentData),
            'Locker assignment retrieved successfully.'
        );
    }

    public function update(
        UpdateLockerAssigmentsRequest $request,
        int $locker_assigments
    ): JsonResponse {
        $assignmentData = $this->lockerAssigmentsService->update(
            $locker_assigments,
            $request->validated()
        );

        return $this->successResponse(
            new LockerAssigmentsResource($assignmentData),
            'Locker assignment updated successfully.'
        );
    }

    public function destroy(int $locker_assigments): JsonResponse
    {
        $this->lockerAssigmentsService->delete($locker_assigments);

        return $this->successResponse(
            null,
            'Locker assignment deleted successfully.'
        );
    }
}
