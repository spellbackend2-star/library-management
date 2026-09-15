<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\FineResource;
use App\Models\Borrow;
use App\Models\BookingSeat;
use App\Models\Fine;
use App\Models\LockerAssignment;
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
        $query = Fine::query()->with(['member', 'invoice']);

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->integer('member_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->integer('invoice_id'));
        }

        $fines = $request->boolean('all')
            ? $query->get()
            : $query->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => FineResource::collection($fines)->resolve(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $fine = Fine::with(['member', 'invoice', 'borrow', 'bookingSeat', 'lockerAssignment'])->find($id);

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
                'message' => 'No overdue fine created (borrow not due or not late).',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fine created for borrow.',
            'data' => new FineResource($fine->fresh()->load(['invoice'])),
        ], 201);
    }

    public function createFineForBookingSeat(int $bookingSeatId): JsonResponse
    {
        $bookingSeat = BookingSeat::with('member')->find($bookingSeatId);

        if (! $bookingSeat) {
            return response()->json(['success' => false, 'message' => 'Booking seat not found.'], 404);
        }

        $fine = $this->fineService->fineForBookingSeatOnComplete($bookingSeat);

        if (! $fine) {
            return response()->json([
                'success' => false,
                'message' => 'No overdue fine created (seat booking not expired or not late).',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fine created for seat booking.',
            'data' => new FineResource($fine->fresh()->load(['invoice'])),
        ], 201);
    }

    public function createFineForLocker(int $lockerAssignmentId): JsonResponse
    {
        $assignment = LockerAssignment::with('member')->find($lockerAssignmentId);

        if (! $assignment) {
            return response()->json(['success' => false, 'message' => 'Locker assignment not found.'], 404);
        }

        $fine = $this->fineService->fineForLockerOnReturn($assignment);

        if (! $fine) {
            return response()->json([
                'success' => false,
                'message' => 'No overdue fine created (locker not expired or not late).',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fine created for locker assignment.',
            'data' => new FineResource($fine->fresh()->load(['invoice'])),
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $fine = Fine::find($id);

        if (! $fine) {
            return response()->json(['success' => false, 'message' => 'Fine not found.'], 404);
        }

        $fine->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fine deleted successfully.',
        ]);
    }
}