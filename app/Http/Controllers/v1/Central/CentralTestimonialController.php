<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\StoreCentralTestimonialRequest;
use App\Http\Requests\Central\UpdateCentralTestimonialRequest;
use App\Http\Resources\v1\Central\CentralTestimonialResource;
use App\Models\CentralTestimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentralTestimonialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $testimonials = CentralTestimonial::query()
            ->when($request->filled('is_published'), fn ($query) => $query->where('is_published', $request->boolean('is_published')))
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json([
            'success' => true,
            'data' => CentralTestimonialResource::collection($testimonials->getCollection())->resolve(),
            'meta' => [
                'current_page' => $testimonials->currentPage(),
                'per_page' => $testimonials->perPage(),
                'total' => $testimonials->total(),
                'last_page' => $testimonials->lastPage(),
            ],
        ]);
    }

    public function store(StoreCentralTestimonialRequest $request): JsonResponse
    {
        $testimonial = CentralTestimonial::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => (new CentralTestimonialResource($testimonial))->resolve(),
        ], 201);
    }

    public function show(CentralTestimonial $testimonial): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => (new CentralTestimonialResource($testimonial))->resolve(),
        ]);
    }

    public function update(UpdateCentralTestimonialRequest $request, CentralTestimonial $testimonial): JsonResponse
    {
        $testimonial->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => (new CentralTestimonialResource($testimonial->fresh()))->resolve(),
        ]);
    }

    public function destroy(CentralTestimonial $testimonial): JsonResponse
    {
        $testimonial->delete();

        return response()->json(['success' => true, 'message' => 'Testimonial deleted.']);
    }
}
