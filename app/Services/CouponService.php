<?php

namespace App\Services;

use App\Models\Coupon;
use App\Repositories\Interface\CouponInterface;

class CouponService
{
    public function __construct(
        protected CouponInterface $couponRepository
    ) {}

    public function getAll()
    {
        return $this->couponRepository->all();
    }

    public function getById(int $id): ?Coupon
    {
        return $this->couponRepository->find($id);
    }

    public function create(array $data): Coupon
    {
        return $this->couponRepository->create($data);
    }

    public function update(int $id, array $data): Coupon
    {
        return $this->couponRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->couponRepository->delete($id);
    }
}
