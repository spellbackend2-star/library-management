<?php

namespace App\Services;

use App\Models\Locker;
use App\Repositories\Interface\LockerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class LockerService
{
    public function __construct(
        protected LockerInterface $lockerRepository
    ) {}

    public function getAll(array $filters = []): array
    {
        return $this->lockerRepository->getAll($filters);
    }

    public function getAllWithoutPagination()
    {
        return $this->lockerRepository->all();
    }

    public function getById(int $id): ?Locker
    {
        return $this->lockerRepository->find($id);
    }

    public function create(array $data): Locker
    {
        return $this->lockerRepository->create($data);
    }

    public function update(int $id, array $data): Locker
    {
        return $this->lockerRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $locker = $this->lockerRepository->find($id);

        if (!$locker) {
            abort(404, 'Locker not found.');
        }

        if (
            $locker->status === 'assigned'
            || $locker->assignments()->where('status', 'active')->exists()
        ) {
            throw new ConflictHttpException(
                'This locker cannot be deleted while it is assigned. Return or release the locker first.'
            );
        }

        if ($locker->assignments()->exists()) {
            throw new ConflictHttpException(
                'This locker cannot be deleted because locker assignment records are linked to it.'
            );
        }

        return $this->lockerRepository->delete($id);
    }
}
