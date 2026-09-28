<?php

namespace App\Repositories\Interface;

use App\Models\CentralSetting;

interface CentralSettingInterface
{
    public function findByGroup(string $group);

    public function findByGroupAndKey(string $group, string $key): ?CentralSetting;

    public function updateOrCreateByKey(
        string $group,
        string $key,
        mixed $value,
        string $type = 'string',
        ?string $description = null
    ): CentralSetting;
}
