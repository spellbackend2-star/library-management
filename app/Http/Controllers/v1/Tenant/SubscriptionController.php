<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->subscriptionService->getAll(
            $request->all()
        );

        $items = new \Illuminate\Database\Eloquent\Collection($result['data'] ?? []);
        $items = $items->loadMissing(['plan', 'tenant']);

        return response()->json([
            'success' => true,
            'message' => 'Subscriptions retrieved successfully.',
            'data' => SubscriptionResource::collection($items),
            'meta' => $result['meta'],
        ]);
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tenant = tenant();

        if ($tenant && isset($tenant->id)) {
            $data['tenant_id'] = $tenant->id;
        }

        $subscription = $this->subscriptionService->create($data);

        return $this->successResponse(
            new SubscriptionResource($subscription->load(['plan', 'payments'])),
            'Subscription created successfully.',
            201
        );
    }

    public function show(Subscription $subscription): JsonResponse
    {
        $subscription->load(['plan', 'tenant', 'payments']);

        return $this->successResponse(
            new SubscriptionResource($subscription),
            'Subscription retrieved successfully.'
        );
    }

    public function update(
        UpdateSubscriptionRequest $request,
        Subscription $subscription
    ): JsonResponse {
        $subscription = $this->subscriptionService->update(
            $subscription->id,
            $request->validated()
        );

        return $this->successResponse(
            new SubscriptionResource($subscription->load(['plan', 'payments'])),
            'Subscription updated successfully.'
        );
    }

    public function destroy(Subscription $subscription): JsonResponse
    {
        $this->subscriptionService->delete($subscription->id);

        return $this->successResponse(
            null,
            'Subscription deleted successfully.'
        );
    }
}
