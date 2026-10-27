<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\UpdateCentralContactMessageRequest;
use App\Http\Resources\v1\Central\CentralContactMessageResource;
use App\Models\CentralContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentralContactMessageController extends Controller
{
    public function show(CentralContactMessage $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => (new CentralContactMessageResource($message))->resolve(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $messages = CentralContactMessage::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest('id')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json([
            'success' => true,
            'data' => CentralContactMessageResource::collection($messages->getCollection())->resolve(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'last_page' => $messages->lastPage(),
            ],
        ]);
    }

    public function update(UpdateCentralContactMessageRequest $request, CentralContactMessage $message): JsonResponse
    {
        $message->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => (new CentralContactMessageResource($message->fresh()))->resolve(),
        ]);
    }

    public function destroy(CentralContactMessage $message): JsonResponse
    {
        $message->delete();

        return response()->json(['success' => true, 'message' => 'Contact message deleted.']);
    }
}
