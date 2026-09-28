<?php

namespace App\Repositories\Eloquent;

use App\Models\CentralSetting;
use App\Repositories\Interface\CentralSettingInterface;

class CentralSettingRepository implements CentralSettingInterface
{
    public function findByGroup(string $group)
    {
        return CentralSetting::ofGroup($group)->orderBy('key')->get();
    }

    public function findByGroupAndKey(string $group, string $key): ?CentralSetting
    {
        return CentralSetting::ofGroup($group)->byKey($key)->first();
    }

    public function updateOrCreateByKey(
        string $group,
        string $key,
        mixed $value,
        string $type = 'string',
        ?string $description = null
    ): CentralSetting {
        return CentralSetting::updateOrCreate(
            [
                'group' => $group,
                'key' => $key,
            ],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description,
                'is_locked' => false,
            ]
        );
    }
}
