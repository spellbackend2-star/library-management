<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\StoreSettingRequest;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(
        protected SettingService $settingService
    ) {}

    public function index(Request $request)
    {
        return SettingResource::collection(
            $this->settingService->getAll($request->query('group'))
        );
    }

    public function store(StoreSettingRequest $request): SettingResource
    {
        $setting = $this->settingService->create(
            $request->validated()
        );

        return new SettingResource($setting);
    }

    public function show(Setting $setting): SettingResource
    {
        return new SettingResource($setting);
    }

    public function update(
        UpdateSettingRequest $request,
        Setting $setting
    ): SettingResource {
        $settingData = $this->settingService->update(
            $setting->id,
            $request->validated()
        );

        return new SettingResource($settingData);
    }

    public function destroy(Setting $setting): JsonResponse
    {
        $this->settingService->delete($setting->id);

        return response()->json([
            'message' => 'Setting deleted successfully.',
        ]);
    }
}
