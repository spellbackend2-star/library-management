<?php

namespace App\Repositories\Interface;

use App\Models\Setting;

interface SettingInterface
{
    public function all();

    public function find(int $id): ?Setting;

    public function findByGroup(string $group);

    public function findByGroupAndKey(string $group, string $key): ?Setting;

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id): bool;
}
