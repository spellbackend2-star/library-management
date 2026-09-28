<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\UpdateCentralSettingGroupRequest;
use App\Services\CentralSettingService;
use Illuminate\Http\JsonResponse;

class CentralSettingController extends Controller
{
    public function __construct(
        protected CentralSettingService $centralSettingService
    ) {}

    /**
     * Get every central settings group at once.
     */
    public function index(): JsonResponse
    {
        $data = [];

        foreach (CentralSettingService::groups() as $group) {
            $data[$group] = $this->centralSettingService->getGroup($group);
        }

        return response()->json([
            'success' => true,
            'message' => 'Central settings retrieved successfully.',
            'data' => $data,
        ]);
    }

    /**
     * Get a central settings group. Supported groups are general,
     * website, payment, smtp, sms and notification.
     */
    public function show(string $group): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => ucfirst($group).' settings retrieved successfully.',
            'group' => $group,
            'data' => $this->centralSettingService->getGroup($group),
        ]);
    }

    /**
     * Update a central settings group. Only the keys sent in the
     * request body are written, the rest keep their current value.
     */
    public function update(UpdateCentralSettingGroupRequest $request): JsonResponse
    {
        $group = $request->group();

        $settings = $this->centralSettingService->updateGroup(
            $group,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => ucfirst($group).' settings updated successfully.',
            'group' => $group,
            'data' => $settings,
        ]);
    }

    /**
     * Get the central general settings.
     */
    public function general(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Central general settings retrieved successfully.',
            'group' => CentralSettingService::GENERAL_GROUP,
            'data' => $this->centralSettingService->general(),
        ]);
    }

    /**
     * Update the central general settings.
     */
    public function updateGeneral(UpdateCentralSettingGroupRequest $request): JsonResponse
    {
        $settings = $this->centralSettingService->updateGeneral(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Central general settings updated successfully.',
            'group' => CentralSettingService::GENERAL_GROUP,
            'data' => $settings,
        ]);
    }
}
