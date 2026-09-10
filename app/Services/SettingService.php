<?php

namespace App\Services;

use App\Models\Setting;
use App\Repositories\Interface\SettingInterface;

class SettingService
{
    public function __construct(
        protected SettingInterface $settingRepository
    ) {}

    public function getAll(?string $group = null)
    {
        if ($group) {
            return $this->settingRepository->findByGroup($group);
        }

        return $this->settingRepository->all();
    }

    public function findById(int $id): ?Setting
    {
        return $this->settingRepository->find($id);
    }

    public function findByGroupAndKey(string $group, string $key): ?Setting
    {
        return $this->settingRepository->findByGroupAndKey($group, $key);
    }

    public function create(array $data)
    {
        return $this->settingRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        $setting = $this->settingRepository->find($id);

        if (! $setting) {
            throw new \Exception('Setting not found.');
        }

        if ($setting->is_locked) {
            throw new \Exception('Locked settings cannot be modified.');
        }

        return $this->settingRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $setting = $this->settingRepository->find($id);

        if (! $setting) {
            throw new \Exception('Setting not found.');
        }

        if ($setting->is_locked) {
            throw new \Exception('Locked settings cannot be deleted.');
        }

        return $this->settingRepository->delete($id);
    }

    public function get(string $key, string $group = 'general', $default = null)
    {
        $setting = $this->settingRepository->findByGroupAndKey($group, $key);

        return $setting ? $setting->value : $default;
    }
}
