<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Locker\StoreLockerRequest;
use App\Http\Requests\Locker\UpdateLockerRequest;
use App\Http\Resources\LockerResource;
use App\Services\LockerService;
use App\Models\Locker;
use Illuminate\Http\JsonResponse;

class LockerController extends Controller
{
    public function __construct(
        protected LockerService $lockerService
    ) {}

    public function index()
    {
        return LockerResource::collection(
            $this->lockerService->getAll()
        );
    }

    public function store(StoreLockerRequest $request): LockerResource
    {
        $locker = $this->lockerService->create(
            $request->validated()
        );

        return new LockerResource($locker);
    }

    public function show(Locker $locker): LockerResource
    {
        return new LockerResource($locker);
    }

    public function update(
        UpdateLockerRequest $request,
        Locker $locker
    ): LockerResource {
        $lockerData = $this->lockerService->update(
            $locker->id,
            $request->validated()
        );

        return new LockerResource($lockerData);
    }

    public function destroy(Locker $locker): JsonResponse
    {
        $this->lockerService->delete($locker->id);

        return response()->json([
            'message' => 'Locker deleted successfully.',
        ]);
    }
}
