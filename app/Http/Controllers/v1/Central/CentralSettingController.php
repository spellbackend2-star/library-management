<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\UpdateCentralSettingGroupRequest;
use App\Services\CentralSettingService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;

class CentralSettingController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected CentralSettingService $centralSettingService
    ) {}

    /**
     * Get all central settings groups.
     */
    public function index(): JsonResponse
    {
        $data = [];

        foreach (CentralSettingService::groups() as $group) {
            $data[$group] = $this->centralSettingService->getGroup($group);
        }

        return $this->successResponse(
            $data,
            'Central settings retrieved successfully.'
        );
    }

    /**
     * Get a central settings group.
     */
    public function show(string $group): JsonResponse
    {
        return $this->successResponse(
            $this->centralSettingService->getGroup($group),
            ucfirst($group) . ' settings retrieved successfully.',
            200,
            ['group' => $group]
        );
    }

    /**
     * Update a central settings group.
     */
    public function update(UpdateCentralSettingGroupRequest $request): JsonResponse
    {
        $group = $request->group();

        $settings = $this->centralSettingService->updateGroup(
            $group,
            $request->validated()
        );

        return $this->successResponse(
            $settings,
            ucfirst($group) . ' settings updated successfully.',
            200,
            ['group' => $group]
        );
    }

    /**
     * Get central general settings.
     */
    public function general(): JsonResponse
    {
        return $this->successResponse(
            $this->centralSettingService->general(),
            'Central general settings retrieved successfully.',
            200,
            ['group' => CentralSettingService::GENERAL_GROUP]
        );
    }

    /**
     * Update central general settings.
     */
    public function updateGeneral(
        UpdateCentralSettingGroupRequest $request
    ): JsonResponse {
        $settings = $this->centralSettingService->updateGeneral(
            $request->validated()
        );

        return $this->successResponse(
            $settings,
            'Central general settings updated successfully.',
            200,
            ['group' => CentralSettingService::GENERAL_GROUP]
        );
    }
}
