<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fine\CancelFineRequest;
use App\Http\Requests\Fine\FineIndexRequest;
use App\Http\Requests\Fine\StoreManualFineRequest;
use App\Http\Requests\Fine\UpdateFineAmountRequest;
use App\Http\Resources\FineResource;
use App\Services\Interface\FineServiceInterface;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;

class FineController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected FineServiceInterface $fineService
    ) {}

    public function index(FineIndexRequest $request): JsonResponse
    {
        $result = $this->fineService->getAll($request->validated());

        return $this->successResponse(
            FineResource::collection($result['data'])->resolve(),
            'Fines retrieved successfully.',
            200,
            $result['meta'],
        );
    }

    public function show(int $id): JsonResponse
    {
        $fine = $this->fineService->find($id);

        if (! $fine) {
            return response()->json(['success' => false, 'message' => 'Fine not found.'], 404);
        }

        return $this->successResponse(
            new FineResource($fine),
            'Fine retrieved successfully.',
        );
    }

    public function createFineForBorrow(int $borrowId): JsonResponse
    {
        $borrow = $this->fineService->findBorrow($borrowId);

        if (! $borrow) {
            return response()->json(['success' => false, 'message' => 'Borrow not found.'], 404);
        }

        $fine = $this->fineService->fineForBorrowOnReturn($borrow);

        if (! $fine) {
            return response()->json([
                'success' => false,
                'message' => 'No book overdue fine created (the book must be returned after its due date).',
            ], 422);
        }

        return $this->successResponse(
            new FineResource($fine->fresh()->load(['invoice'])),
            'Fine created for borrow.',
            201,
        );
    }

    public function createManual(StoreManualFineRequest $request): JsonResponse
    {
        $fine = $this->fineService->createManualFine(
            memberId: $request->integer('member_id'),
            amount: (float) $request->validated('amount'),
            reason: $request->validated('reason'),
            note: $request->validated('note'),
            createdBy: $request->user()?->id,
        );

        return $this->successResponse(
            new FineResource($fine),
            'Manual fine created successfully.',
            201,
        );
    }

    public function updateAmount(UpdateFineAmountRequest $request, int $id): JsonResponse
    {
        $fine = $this->fineService->updateFineAmount(
            fineId: $id,
            amount: (float) $request->validated('amount'),
            reason: $request->validated('reason'),
            notes: $request->exists('notes')
                ? $request->validated('notes')
                : $request->validated('note'),
            notesProvided: $request->exists('notes') || $request->exists('note'),
            actorUserId: $request->user()?->id,
        );

        return $this->successResponse(
            new FineResource($fine),
            'Fine updated successfully.',
        );
    }

    public function cancel(CancelFineRequest $request, int $id): JsonResponse
    {
        $fine = $this->fineService->cancelFine(
            fineId: $id,
            actorUserId: $request->user()?->id,
            note: $request->validated('note'),
        );

        return $this->successResponse(
            new FineResource($fine),
            'Fine cancelled successfully.',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->fineService->deleteFine($id);

        return $this->successResponse(
            null,
            'Fine deleted successfully.',
        );
    }
}
