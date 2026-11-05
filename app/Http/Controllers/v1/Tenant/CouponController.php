<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Services\CouponService;
use App\Models\Coupon;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use ResponseMessage;
    public function __construct(
        protected CouponService $couponService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'sort_by',
            'sort_order',
            'per_page',
            'page',
        ]);

        $result = $this->couponService->getAll($filters);

        return $this->successResponse(
            CouponResource::collection($result['data']),
            'Coupons retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function store(StoreCouponRequest $request): CouponResource
    {
        $coupon = $this->couponService->create(
            $request->validated()
        );

        return new CouponResource($coupon);
    }

    public function show(Coupon $coupon): CouponResource
    {
        return new CouponResource($coupon);
    }

    public function update(
        UpdateCouponRequest $request,
        Coupon $coupon
    ): CouponResource {
        $couponData = $this->couponService->update(
            $coupon->id,
            $request->validated()
        );

        return new CouponResource($couponData);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $this->couponService->delete($coupon->id);

        return response()->json([
            'message' => 'Coupon deleted successfully.',
        ]);
    }
}
