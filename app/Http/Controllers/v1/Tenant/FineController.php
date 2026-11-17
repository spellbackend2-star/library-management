<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fine\CancelFineRequest;
use App\Http\Requests\Fine\StoreManualFineRequest;
use App\Http\Requests\Fine\UpdateFineAmountRequest;
use App\Http\Resources\FineResource;
use App\Models\Borrow;
use App\Models\Fine;
use App\Services\FineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FineController extends Controller
{
    public function __construct(
        protected FineService $fineService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Fine::query()->with([
            'member',
            'invoice',
            'borrow.copy.edition.book',
            'bookingSeat.seat',
            'lockerAssignment.locker',
            'creator',
            'adjustments',
        ]);

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->integer('member_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->integer('invoice_id'));
        }

        if ($request->boolean('all')) {
            $fines = $query->get();
            $meta = [
                'total' => $fines->count(),
                'current_page' => 1,
                'per_page' => $fines->count(),
                'last_page' => 1,
                'first_page_url' => null,
                'last_page_url' => null,
                'next_page_url' => null,
                'prev_page_url' => null,
            ];
        } else {
            $paginator = $query
                ->paginate((int) $request->input('per_page', 15))
                ->withQueryString();
            $fines = $paginator->getCollection();
            $meta = [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'first_page_url' => $paginator->url(1),
                'last_page_url' => $paginator->url($paginator->lastPage()),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => FineResource::collection($fines)->resolve(),
            'meta' => $meta,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $fine = Fine::with([
            'member',
            'invoice',
            'borrow.copy.edition.book',
            'bookingSeat.seat',
            'lockerAssignment.locker',
            'creator',
            'adjustments',
        ])->find($id);

        if (! $fine) {
            return response()->json(['success' => false, 'message' => 'Fine not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new FineResource($fine),
        ]);
    }

    public function createFineForBorrow(int $borrowId): JsonResponse
    {
        $borrow = Borrow::with('member')->find($borrowId);

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

        return response()->json([
            'success' => true,
            'message' => 'Fine created for borrow.',
            'data' => new FineResource($fine->fresh()->load(['invoice'])),
        ], 201);
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

        return response()->json([
            'success' => true,
            'message' => 'Manual fine created successfully.',
            'data' => new FineResource($fine),
        ], 201);
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

        return response()->json([
            'success' => true,
            'message' => 'Fine updated successfully.',
            'data' => new FineResource($fine),
        ]);
    }

    public function cancel(CancelFineRequest $request, int $id): JsonResponse
    {
        $fine = $this->fineService->cancelFine(
            fineId: $id,
            actorUserId: $request->user()?->id,
            note: $request->validated('note'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Fine cancelled successfully.',
            'data' => new FineResource($fine),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->fineService->deleteFine($id);

        return response()->json([
            'success' => true,
            'message' => 'Fine deleted successfully.',
        ]);
    }
}
