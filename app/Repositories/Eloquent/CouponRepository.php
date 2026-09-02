<?php

namespace App\Repositories\Eloquent;

use App\Models\Coupon;
use App\Repositories\Interface\CouponInterface;

class CouponRepository implements CouponInterface
{
    public function all()
    {
        return Coupon::latest()->get();
    }

    public function find(int $id): ?Coupon
    {
        return Coupon::find($id);
    }

    public function create(array $data): Coupon
    {
        return Coupon::create($data);
    }

    public function update(int $id, array $data): Coupon
    {
        $coupon = Coupon::findOrFail($id);

        $coupon->update($data);

        return $coupon->fresh();
    }

    public function delete(int $id): bool
    {
        return Coupon::findOrFail($id)->delete();
    }
}
