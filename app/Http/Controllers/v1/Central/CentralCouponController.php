<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentralCouponController extends Controller
{
    use ResponseMessage;

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $coupons = Coupon::latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->successResponse(
            CouponResource::collection($coupons),
            'Coupons retrieved successfully.',
            200,
            [
                'total' => $coupons->total(),
                'last_page' => $coupons->lastPage(),
                'current_page' => $coupons->currentPage(),
                'per_page' => $coupons->perPage(),
                'first_page_url' => $coupons->url(1),
                'last_page_url' => $coupons->url($coupons->lastPage()),
                'next_page_url' => $coupons->nextPageUrl(),
                'prev_page_url' => $coupons->previousPageUrl(),
            ]
        );
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $coupon = Coupon::create($request->validated());

        return $this->successResponse(
            new CouponResource($coupon),
            'Coupon created successfully.',
            201
        );
    }

    public function show(Coupon $coupon): JsonResponse
    {
        return $this->successResponse(
            new CouponResource($coupon),
            'Coupon retrieved successfully.'
        );
    }

    public function update(
        UpdateCouponRequest $request,
        Coupon $coupon
    ): JsonResponse {
        $coupon->update($request->validated());

        return $this->successResponse(
            new CouponResource($coupon->fresh()),
            'Coupon updated successfully.'
        );
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();

        return $this->successResponse(
            null,
            'Coupon deleted successfully.'
        );
    }
}