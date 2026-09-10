<?php

namespace App\Repositories\Eloquent;

use App\Models\Setting;
use App\Repositories\Interface\SettingInterface;

class SettingRepository implements SettingInterface
{
    public function all()
    {
        return Setting::orderBy('group')->orderBy('key')->get();
    }

    public function find(int $id): ?Setting
    {
        return Setting::find($id);
    }

    public function findByGroup(string $group)
    {
        return Setting::ofGroup($group)->orderBy('key')->get();
    }

    public function findByGroupAndKey(string $group, string $key): ?Setting
    {
        return Setting::ofGroup($group)->byKey($key)->first();
    }

    public function create(array $data)
    {
        return Setting::create($data);
    }

    public function update(int $id, array $data)
    {
        $setting = Setting::findOrFail($id);

        $setting->update($data);

        return $setting->fresh();
    }

    public function delete(int $id): bool
    {
        return Setting::findOrFail($id)->delete();
    }
}
