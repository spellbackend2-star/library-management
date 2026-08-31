<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\SeatCategory\StoreSeatCategoryRequest;
use App\Http\Requests\SeatCategory\UpdateSeatCategoryRequest;
use App\Http\Resources\SeatCategoryResource;
use App\Services\SeatCategoryService;
use App\Models\SeatCategory;
use Illuminate\Http\JsonResponse;

class SeatCategoryController extends Controller
{
    public function __construct(
        protected SeatCategoryService $seatCategoryService
    ) {}

    public function index()
    {
        return SeatCategoryResource::collection(
            $this->seatCategoryService->getAll()
        );
    }

    public function store(StoreSeatCategoryRequest $request): SeatCategoryResource
    {
        $category = $this->seatCategoryService->create(
            $request->validated()
        );

        return new SeatCategoryResource($category);
    }

    public function show(SeatCategory $seat_category): SeatCategoryResource
    {
        return new SeatCategoryResource($seat_category);
    }

    public function update(
        UpdateSeatCategoryRequest $request,
        SeatCategory $seat_category
    ): SeatCategoryResource {
        $categoryData = $this->seatCategoryService->update(
            $seat_category->id,
            $request->validated()
        );

        return new SeatCategoryResource($categoryData);
    }

    public function destroy(SeatCategory $seat_category): JsonResponse
    {
        $this->seatCategoryService->delete($seat_category->id);

        return response()->json([
            'message' => 'Seat category deleted successfully.',
        ]);
    }
}
