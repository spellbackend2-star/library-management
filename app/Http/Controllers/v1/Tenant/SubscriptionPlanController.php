<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionPlan\StoreSubscriptionPlanRequest;
use App\Http\Requests\SubscriptionPlan\UpdateSubscriptionPlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionPlanService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected SubscriptionPlanService $subscriptionPlanService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->subscriptionPlanService->getAll(
            $request->all()
        );

        return response()->json([
            'success' => true,
            'message' => 'Subscription plans retrieved successfully.',
            'data' => SubscriptionPlanResource::collection($result['data']),
            'meta' => $result['meta'],
        ]);
    }

    public function store(StoreSubscriptionPlanRequest $request): JsonResponse
    {
        $subscriptionPlan = $this->subscriptionPlanService->create(
            $request->validated()
        );

        return $this->successResponse(
            new SubscriptionPlanResource($subscriptionPlan),
            'Subscription plan created successfully.',
            201
        );
    }

    public function show(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        return $this->successResponse(
            new SubscriptionPlanResource($subscriptionPlan),
            'Subscription plan retrieved successfully.'
        );
    }

    public function update(
        UpdateSubscriptionPlanRequest $request,
        SubscriptionPlan $subscriptionPlan
    ): JsonResponse {
        $subscriptionPlan = $this->subscriptionPlanService->update(
            $subscriptionPlan->id,
            $request->validated()
        );

        return $this->successResponse(
            new SubscriptionPlanResource($subscriptionPlan),
            'Subscription plan updated successfully.'
        );
    }

    public function destroy(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $this->subscriptionPlanService->delete($subscriptionPlan->id);

        return $this->successResponse(
            null,
            'Subscription plan deleted successfully.'
        );
    }
}
