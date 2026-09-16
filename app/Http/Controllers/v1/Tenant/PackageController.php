<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Package\StorePackageRequest;
use App\Http\Requests\Package\UpdatePackageRequest;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use App\Services\PackageService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected PackageService $packageService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->packageService->getAll(
            $request->all()
        );

        return response()->json([
            'success' => true,
            'message' => 'Packages retrieved successfully.',
            'data' => PackageResource::collection($result['data']),
            'meta' => $result['meta'],
        ]);
    }

    public function store(StorePackageRequest $request): JsonResponse
    {
        $package = $this->packageService->create(
            $request->validated()
        );

        return $this->successResponse(
            new PackageResource($package),
            'Package created successfully.',
            201
        );
    }

    public function show(Package $package): JsonResponse
    {
        return $this->successResponse(
            new PackageResource($package),
            'Package retrieved successfully.'
        );
    }

    public function update(
        UpdatePackageRequest $request,
        Package $package
    ): JsonResponse {
        $package = $this->packageService->update(
            $package->id,
            $request->validated()
        );

        return $this->successResponse(
            new PackageResource($package),
            'Package updated successfully.'
        );
    }

    public function destroy(Package $package): JsonResponse
    {
        $this->packageService->delete($package->id);

        return $this->successResponse(
            null,
            'Package deleted successfully.'
        );
    }
}
