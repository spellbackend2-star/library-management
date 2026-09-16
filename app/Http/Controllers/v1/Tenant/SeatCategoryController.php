<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\SeatCategory\StoreSeatCategoryRequest;
use App\Http\Requests\SeatCategory\UpdateSeatCategoryRequest;
use App\Http\Resources\SeatCategoryResource;
use App\Services\SeatCategoryService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeatCategoryController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected SeatCategoryService $seatCategoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'name',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $result = $this->seatCategoryService->getAll($filters);

        return $this->successResponse(
            SeatCategoryResource::collection($result['data']),
            'Seat categories retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function store(StoreSeatCategoryRequest $request): JsonResponse
    {
        $category = $this->seatCategoryService->create(
            $request->validated()
        );

        return $this->successResponse(
            new SeatCategoryResource($category),
            'Seat category created successfully.',
            201
        );
    }

    public function show(int $seat_category): JsonResponse
    {
        $categoryData = $this->seatCategoryService->getById($seat_category);

        abort_if(!$categoryData, 404, 'Seat category not found.');

        return $this->successResponse(
            new SeatCategoryResource($categoryData),
            'Seat category retrieved successfully.'
        );
    }

    public function update(
        UpdateSeatCategoryRequest $request,
        int $seat_category
    ): JsonResponse {
        $categoryData = $this->seatCategoryService->update(
            $seat_category,
            $request->validated()
        );

        return $this->successResponse(
            new SeatCategoryResource($categoryData),
            'Seat category updated successfully.'
        );
    }

    public function destroy(int $seat_category): JsonResponse
    {
        $this->seatCategoryService->delete($seat_category);

        return $this->successResponse(
            null,
            'Seat category deleted successfully.'
        );
    }
}
