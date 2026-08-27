<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Package\StorePackageRequest;
use App\Http\Requests\Package\UpdatePackageRequest;
use App\Http\Resources\PackageResource;
use App\Services\PackageService;
use App\Models\Package;
use Illuminate\Http\JsonResponse;

class PackageController extends Controller
{
    public function __construct(
        protected PackageService $packageService
    ) {}

    public function index()
    {
        return PackageResource::collection(
            $this->packageService->getAll()
        );
    }

    public function store(StorePackageRequest $request): PackageResource
    {
        $package = $this->packageService->create(
            $request->validated()
        );

        return new PackageResource($package);
    }

    public function show(Package $package): PackageResource
    {
        return new PackageResource($package);
    }

    public function update(
        UpdatePackageRequest $request,
        Package $package
    ): PackageResource {
        $packageData = $this->packageService->update(
            $package->id,
            $request->validated()
        );

        return new PackageResource($packageData);
    }

    public function destroy(Package $package): JsonResponse
    {
        $this->packageService->delete($package->id);

        return response()->json([
            'message' => 'Package deleted successfully.',
        ]);
    }
}
