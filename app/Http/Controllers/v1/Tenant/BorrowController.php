<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Borrow\StoreBorrowRequest;
use App\Http\Requests\Borrow\UpdateBorrowRequest;
use App\Http\Resources\BorrowResource;
use App\Services\BorrowService;
use App\Models\Borrow;
use Illuminate\Http\JsonResponse;

class BorrowController extends Controller
{
    public function __construct(
        protected BorrowService $borrowService
    ) {}

    public function index()
    {
        return BorrowResource::collection(
            $this->borrowService->getAll()
        );
    }

    public function store(StoreBorrowRequest $request): BorrowResource
    {
        $borrow = $this->borrowService->create(
            $request->validated()
        );

        return new BorrowResource($borrow);
    }

    public function show(Borrow $borrow): BorrowResource
    {
        return new BorrowResource($borrow);
    }

    public function update(
        UpdateBorrowRequest $request,
        Borrow $borrow
    ): BorrowResource {
        $borrowData = $this->borrowService->update(
            $borrow->id,
            $request->validated()
        );

        return new BorrowResource($borrowData);
    }

    public function destroy(Borrow $borrow): JsonResponse
    {
        $this->borrowService->delete($borrow->id);

        return response()->json([
            'message' => 'Borrow deleted successfully.',
        ]);
    }
}
