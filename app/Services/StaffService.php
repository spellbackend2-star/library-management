<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\User;
use App\Repositories\Interface\StaffInterface;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class StaffService
{
    public function __construct(
        protected StaffInterface $staffRepository
    ) {}

    public function getAll()
    {
        return $this->staffRepository->all();
    }

    public function getById(int $id): ?Staff
    {
        return $this->staffRepository->find($id);
    }

    public function create(array $data): Staff
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $staffData = [
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'] ?? true,
            ];

            if (!empty($data['hire_date'])) {
                $staffData['hire_date'] = $data['hire_date'];
            }

            $staff = Staff::create($staffData);

            if (!empty($data['role'])) {
                $role = Role::where('name', $data['role'])
                    ->where('guard_name', 'api')
                    ->first();

                if ($role) {
                    $user->assignRole($data['role']);
                }
            }

            return $staff;
        });
    }

    public function update(int $id, array $data): Staff
    {
        return DB::transaction(function () use ($id, $data) {
            $staff = $this->staffRepository->update($id, $data);

            if (isset($data['email']) && $staff->user) {
                $staff->user->update([
                    'email' => $data['email'],
                ]);
            }

            if (isset($data['role'])) {
                $staff->user?->syncRoles([$data['role']]);
            }

            return $staff->fresh();
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $staff = Staff::findOrFail($id);

            if ($staff->user) {
                $staff->user->delete();
            }

            return $this->staffRepository->delete($id);
        });
    }

    public function assignRole(int $staffId, string $roleName): Staff
    {
        $staff = $this->staffRepository->find($staffId);

        abort_if(!$staff, 404, 'Staff not found.');

        $staff->user?->syncRoles([$roleName]);

        return $staff->fresh();
    }

    public function activate(int $staffId): Staff
    {
        $staff = $this->staffRepository->update($staffId, [
            'is_active' => true,
        ]);

        return $staff->fresh();
    }

    public function deactivate(int $staffId): Staff
    {
        $staff = $this->staffRepository->update($staffId, [
            'is_active' => false,
        ]);

        return $staff->fresh();
    }
}
