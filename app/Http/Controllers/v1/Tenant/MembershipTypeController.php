<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\MembershipType\StoreMembershipTypeRequest;
use App\Http\Requests\MembershipType\UpdateMembershipTypeRequest;
use App\Http\Resources\MembershipTypeResource;
use App\Services\MembershipTypeService;
use Illuminate\Http\JsonResponse;

class MembershipTypeController extends Controller
{
    public function __construct(
        protected MembershipTypeService $membershipTypeService
    ) {}

    public function index()
    {
        $membershipTypes = $this->membershipTypeService->getAll();

        return MembershipTypeResource::collection($membershipTypes);
    }

    public function store(StoreMembershipTypeRequest $request)
    {
        $membershipType = $this->membershipTypeService->create(
            $request->validated()
        );

        return (new MembershipTypeResource($membershipType))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id)
    {
        $membershipType = $this->membershipTypeService->getById($id);

        if (!$membershipType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Membership type not found.',
            ], 404);
        }

        return new MembershipTypeResource($membershipType);
    }

    public function update(
        UpdateMembershipTypeRequest $request,
        int $id
    ) {
        $membershipType = $this->membershipTypeService->update(
            $id,
            $request->validated()
        );

        if (!$membershipType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Membership type not found.',
            ], 404);
        }

        return new MembershipTypeResource($membershipType);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->membershipTypeService->delete($id);

        if (!$deleted) {
            return response()->json([
                'status' => 'error',
                'message' => 'Membership type not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Membership type deleted successfully.',
        ]);
    }
}
