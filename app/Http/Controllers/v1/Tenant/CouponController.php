<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Services\CouponService;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    public function __construct(
        protected CouponService $couponService
    ) {}

    public function index()
    {
        return CouponResource::collection(
            $this->couponService->getAll()
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
