<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Borrow;
use App\Models\LockerAssignment;
use App\Repositories\Interface\BookingInterface;
use App\Repositories\Interface\BookingSeatInterface;
use App\Repositories\Interface\BorrowInterface;
use App\Repositories\Interface\LockerAssignmentInterface;
use App\Repositories\Interface\MemberInterface;
use App\Repositories\Interface\PackageInterface;
use App\Repositories\Interface\SeatInterface;
use App\Repositories\Interface\CopyInterface;
use App\Repositories\Interface\CouponInterface;
use App\Repositories\Interface\LockerInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class BookingService
{
    public function __construct(
        protected BookingInterface $bookingRepository,
        protected BookingSeatInterface $bookingSeatRepository,
        protected BorrowInterface $borrowRepository,
        protected LockerAssignmentInterface $lockerAssigmentsRepository,
        protected MemberInterface $memberRepository,
        protected PackageInterface $packageRepository,
        protected SeatInterface $seatRepository,
        protected CopyInterface $copyRepository,
        protected LockerInterface $lockerRepository,
        protected CouponInterface $couponRepository,
    ) {}

    public function getAll()
    {
        return $this->bookingRepository->all();
    }

    public function getById(int $id): ?Booking
    {
        return $this->bookingRepository->find($id);
    }

    public function create(array $data): Booking
    {
        $member = $this->memberRepository->find($data['member_id']);

        if (!$member) {
            throw new \Exception('Member not found.');
        }

        $package = $member->package;

        if (!$package || !$package->is_active) {
            throw new \Exception('Member does not have an active package.');
        }

        $staffId = $data['staff_id'] ?? null;
        $bookedById = null;

        if (Auth::check()) {
            $user = Auth::user();
            $staff = \App\Models\Staff::where('user_id', $user->id)->first();

            if ($staff) {
                $bookedById = $staff->id;
                $staffId = $staffId ?? $staff->id;
            }
        }

        $bookings = $data['bookings'] ?? [];

        if (empty($bookings)) {
            throw new \Exception('No bookings provided.');
        }

        foreach ($bookings as $bookingData) {
            $bookingData['staff_id'] = $staffId;
            $this->validateBooking($bookingData, $package, $data['member_id']);
        }

        $finalAmount = isset($data['amount']) && $data['amount'] !== null
            ? (float) $data['amount']
            : (float) $package->price;

        $couponId = $data['coupon_id'] ?? null;
        $discountAmount = 0.0;
        $coupon = null;

        if ($couponId) {
            $coupon = $this->couponRepository->find($couponId);

            if (!$coupon) {
                throw new \Exception('Coupon not found.');
            }

            if (!$coupon->is_active) {
                throw new \Exception('Coupon is not active.');
            }

            if ($coupon->valid_from && now()->lt($coupon->valid_from)) {
                throw new \Exception('Coupon is not yet valid.');
            }

            if ($coupon->valid_until && now()->gt($coupon->valid_until)) {
                throw new \Exception('Coupon has expired.');
            }

            if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
                throw new \Exception('Coupon has reached its maximum usage limit.');
            }

            if ($finalAmount < $coupon->min_order_value) {
                throw new \Exception('Order amount does not meet the minimum required for this coupon.');
            }

            if ($coupon->discount_type === 'FLAT') {
                $discountAmount = (float) $coupon->discount_value;
            } else {
                $discountAmount = $finalAmount * ((float) $coupon->discount_value / 100);
            }

            if ($coupon->max_discount !== null && $discountAmount > $coupon->max_discount) {
                $discountAmount = (float) $coupon->max_discount;
            }

            if ($discountAmount > $finalAmount) {
                $discountAmount = $finalAmount;
            }

            $discountAmount = round($discountAmount, 2);
        }

        $parentBooking = DB::transaction(function () use ($data, $package, $bookedById, $finalAmount, $couponId, $discountAmount) {
            return $this->bookingRepository->create([
                'member_id' => $data['member_id'],
                'package_id' => $package->id,
                'booking_type' => 'package',
                'status' => 'pending',
                'amount' => $finalAmount,
                'subtotal' => $finalAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => round($finalAmount - $discountAmount, 2),
                'coupon_id' => $couponId,
                'notes' => $data['notes'] ?? null,
                'booked_by_user_id' => $bookedById,
            ]);
        });

        if ($coupon) {
            $coupon->increment('used_count');
        }

        foreach ($bookings as $bookingData) {
            $type = $bookingData['type'];

            if ($type === 'seat') {
                $this->createSeatBooking($bookingData, $package, $data['member_id'], $data['notes'] ?? null, $parentBooking->id);
            } elseif ($type === 'book') {
                $this->createBookBooking($bookingData, $package, $data['member_id'], $data['notes'] ?? null, $parentBooking->id);
            } elseif ($type === 'locker') {
                $this->createLockerBooking($bookingData, $package, $data['member_id'], $data['notes'] ?? null, $parentBooking->id);
            }
        }

        return $parentBooking->fresh([
            'member',
            'package',
            'coupon',
            'bookingSeats',
            'borrows',
            'lockerAssignments',
        ]);
    }

    protected function validateBooking(array $data, $package, int $memberId): void
    {
        $type = $data['type'];

        if ($type === 'seat') {
            if (!$package->seat_access_allowed) {
                throw new \Exception('Package does not allow seat access.');
            }

            $start = Carbon::parse($data['start_at']);
            $end = Carbon::parse($data['end_at']);
            $hours = $start->diffInHours($end);

            if ($hours > (float) $package->max_seat_hours_per_day) {
                throw new \Exception('Exceeds maximum allowed seat hours per day.');
            }

            $seat = $this->seatRepository->find($data['seat_id']);

            if (!$seat) {
                throw new \Exception('Seat not found.');
            }

            $overlap = BookingSeat::where('seat_id', $data['seat_id'])
                ->where('status', '!=', 'cancelled')
                ->where('start_at', '<', $data['end_at'])
                ->where('end_at', '>', $data['start_at'])
                ->exists();

            if ($overlap) {
                throw new \Exception('Seat is already booked for the selected time.');
            }
        } elseif ($type === 'book') {
            if (!$package->max_book_loans || $package->max_book_loans <= 0) {
                throw new \Exception('Package does not allow book loans.');
            }

            $activeBorrows = Borrow::where('member_id', $memberId)
                ->whereIn('status', ['active', 'overdue'])
                ->count();

            if ($activeBorrows >= $package->max_book_loans) {
                throw new \Exception('Book loan limit exceeded. Active borrows: ' . $activeBorrows . ', Limit: ' . $package->max_book_loans);
            }

            $copy = $this->copyRepository->find($data['copy_id']);

            if (!$copy) {
                throw new \Exception('Copy not found.');
            }

            $existingBorrow = Borrow::where('copy_id', $data['copy_id'])
                ->whereIn('status', ['active', 'overdue'])
                ->exists();

            if ($existingBorrow) {
                throw new \Exception('Copy is already borrowed.');
            }
        } elseif ($type === 'locker') {
            if (!$package->locker_allowed) {
                throw new \Exception('Package does not allow locker usage.');
            }

            $locker = $this->lockerRepository->find($data['locker_id']);

            if (!$locker) {
                throw new \Exception('Locker not found.');
            }

            $existingAssignment = LockerAssignment::where('locker_id', $data['locker_id'])
                ->where('status', 'active')
                ->exists();

            if ($existingAssignment) {
                throw new \Exception('Locker is already assigned.');
            }
        } else {
            throw new \Exception('Invalid booking type: ' . $type);
        }
    }

    protected function createSeatBooking(array $data, $package, int $memberId, ?string $notes, int $parentBookingId): void
    {
        if (!$package->seat_access_allowed) {
            throw new \Exception('Package does not allow seat access.');
        }

        $start = Carbon::parse($data['start_at']);
        $end = Carbon::parse($data['end_at']);
        $hours = $start->diffInHours($end);

        if ($hours > (float) $package->max_seat_hours_per_day) {
            throw new \Exception('Exceeds maximum allowed seat hours per day.');
        }

        $seat = $this->seatRepository->find($data['seat_id']);

        if (!$seat) {
            throw new \Exception('Seat not found.');
        }

        $overlap = BookingSeat::where('seat_id', $data['seat_id'])
            ->where('status', '!=', 'cancelled')
            ->where('start_at', '<', $data['end_at'])
            ->where('end_at', '>', $data['start_at'])
            ->exists();

        if ($overlap) {
            throw new \Exception('Seat is already booked for the selected time.');
        }

        $this->bookingSeatRepository->create([
            'booking_id' => $parentBookingId,
            'member_id' => $memberId,
            'seat_id' => $data['seat_id'],
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'status' => 'booked',
        ]);
    }

    protected function createBookBooking(array $data, $package, int $memberId, ?string $notes, int $parentBookingId): void
    {
        if (!$package->max_book_loans || $package->max_book_loans <= 0) {
            throw new \Exception('Package does not allow book loans.');
        }

        $activeBorrows = Borrow::where('member_id', $memberId)
            ->whereIn('status', ['active', 'overdue'])
            ->count();

        if ($activeBorrows >= $package->max_book_loans) {
            throw new \Exception('Book loan limit exceeded.');
        }

        $copy = $this->copyRepository->find($data['copy_id']);

        if (!$copy) {
            throw new \Exception('Copy not found.');
        }

        $existingBorrow = Borrow::where('copy_id', $data['copy_id'])
            ->whereIn('status', ['active', 'overdue'])
            ->exists();

        if ($existingBorrow) {
            throw new \Exception('Copy is already borrowed.');
        }

        $checkoutDate = Carbon::now();
        $dueDate = $checkoutDate->copy()->addDays((int) $package->max_borrow_days);

        $this->borrowRepository->create([
            'booking_id' => $parentBookingId,
            'copy_id' => $data['copy_id'],
            'member_id' => $memberId,
            'staff_id' => $data['staff_id'] ?? null,
            'checkout_date' => $checkoutDate,
            'due_date' => $dueDate,
            'renewal_count' => 0,
            'status' => 'active',
        ]);
    }

    protected function createLockerBooking(array $data, $package, int $memberId, ?string $notes, int $parentBookingId): void
    {
        if (!$package->locker_allowed) {
            throw new \Exception('Package does not allow locker usage.');
        }

        $locker = $this->lockerRepository->find($data['locker_id']);

        if (!$locker) {
            throw new \Exception('Locker not found.');
        }

        $existingAssignment = LockerAssignment::where('locker_id', $data['locker_id'])
            ->where('status', 'active')
            ->exists();

        if ($existingAssignment) {
            throw new \Exception('Locker is already assigned.');
        }

        $this->lockerAssigmentsRepository->create([
            'booking_id' => $parentBookingId,
            'locker_id' => $data['locker_id'],
            'member_id' => $memberId,
            'assigned_date' => $data['start_at'],
            'expiry_date' => $data['end_at'],
            'status' => 'active',
        ]);
    }

    public function update(int $id, array $data): Booking
    {
        $booking = $this->bookingRepository->find($id);

        if (!$booking) {
            throw new \Exception('Booking not found.');
        }

        return DB::transaction(function () use ($booking, $data) {
            $updated = $this->bookingRepository->update($booking->id, $data);

            if (isset($data['start_at']) || isset($data['end_at'])) {
                if ($booking->booking_type === 'seat' && $booking->bookingSeats) {
                    $seatData = [];
                    if (isset($data['start_at'])) {
                        $seatData['start_at'] = $data['start_at'];
                    }
                    if (isset($data['end_at'])) {
                        $seatData['end_at'] = $data['end_at'];
                    }
                    if (isset($data['status'])) {
                        $seatData['status'] = $data['status'];
                    }
                    $this->bookingSeatRepository->update($booking->bookingSeats->first()->id, $seatData);
                } elseif ($booking->booking_type === 'locker' && $booking->lockerAssignments) {
                    $lockerData = [];
                    if (isset($data['start_at'])) {
                        $lockerData['assigned_date'] = $data['start_at'];
                    }
                    if (isset($data['end_at'])) {
                        $lockerData['expiry_date'] = $data['end_at'];
                    }
                    if (isset($data['status'])) {
                        $lockerData['status'] = $data['status'];
                    }
                    $this->lockerAssigmentsRepository->update($booking->lockerAssignments->first()->id, $lockerData);
                } elseif ($booking->booking_type === 'book' && $booking->borrows) {
                    $borrowData = [];
                    if (isset($data['status'])) {
                        $borrowData['status'] = $data['status'];
                    }
                    if (isset($data['due_date'])) {
                        $borrowData['due_date'] = $data['due_date'];
                    }
                    $this->borrowRepository->update($booking->borrows->first()->id, $borrowData);
                }
            } elseif (isset($data['status'])) {
                if ($booking->booking_type === 'seat' && $booking->bookingSeats) {
                    $this->bookingSeatRepository->update($booking->bookingSeats->first()->id, [
                        'status' => $data['status'],
                    ]);
                } elseif ($booking->booking_type === 'locker' && $booking->lockerAssignments) {
                    $this->lockerAssigmentsRepository->update($booking->lockerAssignments->first()->id, [
                        'status' => $data['status'],
                    ]);
                } elseif ($booking->booking_type === 'book' && $booking->borrows) {
                    $this->borrowRepository->update($booking->borrows->first()->id, [
                        'status' => $data['status'],
                    ]);
                }
            }

            return $updated;
        });
    }

    public function delete(int $id): bool
    {
        $booking = $this->bookingRepository->find($id);

        if (!$booking) {
            throw new \Exception('Booking not found.');
        }

        return DB::transaction(function () use ($booking) {
            if ($booking->booking_type === 'seat' && $booking->bookingSeats) {
                $this->bookingSeatRepository->delete($booking->bookingSeats->first()->id);
            } elseif ($booking->booking_type === 'book' && $booking->borrows) {
                $this->borrowRepository->delete($booking->borrows->first()->id);
            } elseif ($booking->booking_type === 'locker' && $booking->lockerAssignments) {
                $this->lockerAssigmentsRepository->delete($booking->lockerAssignments->first()->id);
            }

            return $this->bookingRepository->delete($booking->id);
        });
    }
}
