<?php

namespace App\Repositories\Interface;

use App\Models\Coupon;

interface CouponInterface
{
    public function all();

    public function find(int $id): ?Coupon;

    public function create(array $data): Coupon;

    public function update(int $id, array $data): Coupon;

    public function delete(int $id): bool;
}
