<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\LockerAssigments\StoreLockerAssigmentsRequest;
use App\Http\Requests\LockerAssigments\UpdateLockerAssigmentsRequest;
use App\Http\Resources\LockerAssigmentsResource;
use App\Services\LockerAssigmentsService;
use App\Models\LockerAssigments;
use App\Models\LockerAssignment;
use Illuminate\Http\JsonResponse;

class LockerAssigmentsController extends Controller
{
    public function __construct(
        protected LockerAssigmentsService $lockerAssigmentsService
    ) {}

    public function index()
    {
        return LockerAssigmentsResource::collection(
            $this->lockerAssigmentsService->getAll()
        );
    }

    public function store(StoreLockerAssigmentsRequest $request): LockerAssigmentsResource
    {
        $assignment = $this->lockerAssigmentsService->create(
            $request->validated()
        );

        return new LockerAssigmentsResource($assignment);
    }

    public function show(LockerAssignment $locker_assigments): LockerAssigmentsResource
    {
        return new LockerAssigmentsResource($locker_assigments);
    }

    public function update(
        UpdateLockerAssigmentsRequest $request,
        LockerAssignment $locker_assigments
    ): LockerAssigmentsResource {
        $assignmentData = $this->lockerAssigmentsService->update(
            $locker_assigments->id,
            $request->validated()
        );

        return new LockerAssigmentsResource($assignmentData);
    }

    public function destroy(LockerAssignment $locker_assigments): JsonResponse
    {
        $this->lockerAssigmentsService->delete($locker_assigments->id);

        return response()->json([
            'message' => 'Locker assignment deleted successfully.',
        ]);
    }
}
