<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Borrow;
use App\Models\Setting;
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
use Illuminate\Validation\ValidationException;

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
        if (empty($data['member_id'])) {
            return $this->createStaffBookBooking($data);
        }

        $member = $this->memberRepository->find($data['member_id']);

        if (!$member) {
            throw new \Exception('Member not found.');
        }

        $package = $member->package;

        if (
            ! $package
            || $member->status !== 'active'
            || ! $member->membership_expiry
            || $member->membership_expiry->lt(Carbon::today())
        ) {
            throw new \Exception('Member does not have an active membership.');
        }

        $unpaidInvoice = app(\App\Services\InvoiceService::class)
            ->findByMember($member->id);

        if ($unpaidInvoice && (float) $unpaidInvoice->paid_amount <= 0) {
            throw new \Exception(
                'Member has an unpaid invoice #'.$unpaidInvoice->invoice_number.'. Please complete payment before booking.'
            );
        }

        $invoiceId = null;
        $paymentId = null;

        $currentInvoice = \App\Models\Invoice::where('member_id', $member->id)
            ->where(function ($query) {
                $query->whereIn('status', ['partially_paid', 'paid'])
                    ->orWhere(function ($overdueQuery) {
                        $overdueQuery->where('status', 'overdue')
                            ->where('paid_amount', '>', 0);
                    });
            })
            ->orderByDesc('id')
            ->first();

        if ($currentInvoice) {
            $invoiceId = $currentInvoice->id;

            $lastPayment = \App\Models\Payment::where('invoice_id', $invoiceId)
                ->orderByDesc('id')
                ->first();

            if ($lastPayment) {
                $paymentId = $lastPayment->id;
            }
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

        $bookings = $this->expandDailyResourceBookings($bookings, $member);
        $resourceBookingMinutesByDate = [];

        foreach ($bookings as $index => &$bookingData) {
            $bookingData['staff_id'] = $staffId;

            if (in_array($bookingData['type'], ['seat', 'locker'], true)) {
                $durationHours = (float) ($bookingData['type'] === 'seat'
                    ? $package->max_seat_hours_per_day
                    : $package->max_locker_hours_per_day);

                if ($durationHours <= 0) {
                    $durationName = $bookingData['type'] === 'seat'
                        ? 'max_seat_hours_per_day'
                        : 'max_locker_hours_per_day';

                    throw ValidationException::withMessages([
                        "bookings.{$index}.start_at" => [
                            "The member's package must define a positive {$durationName} to book this resource.",
                        ],
                    ]);
                }

                $durationMinutes = (int) round($durationHours * 60);
                $startAt = Carbon::parse($bookingData['start_at']);
                $endAt = $startAt->copy()->addMinutes($durationMinutes);

                if (in_array($bookingData['type'], ['seat', 'locker'], true)) {
                    $membershipStart = $member->membership_start?->copy()->startOfDay();
                    $membershipExpiry = $member->membership_expiry?->copy()->endOfDay();

                    if (
                        ($membershipStart && $startAt->lt($membershipStart))
                        || ($membershipExpiry && $endAt->gt($membershipExpiry))
                    ) {
                        throw ValidationException::withMessages([
                            "bookings.{$index}.start_at" => [
                                'Bookings must fit within the member membership dates.',
                            ],
                        ]);
                    }

                    if ($endAt->toDateString() !== $startAt->toDateString()) {
                        throw ValidationException::withMessages([
                            "bookings.{$index}.start_at" => [
                                'A seat or locker booking must end on the same calendar day it starts.',
                            ],
                        ]);
                    }

                    $dateKey = $bookingData['type'].':'.$startAt->toDateString();
                    $maxDailyMinutes = $durationMinutes;
                    $previouslyBookedMinutes = $resourceBookingMinutesByDate[$dateKey]
                        ?? ($bookingData['type'] === 'seat'
                            ? $this->getSeatBookedMinutesForDate($data['member_id'], $startAt)
                            : $this->getLockerBookedMinutesForDate($data['member_id'], $startAt));

                    if ($previouslyBookedMinutes + $durationMinutes > $maxDailyMinutes) {
                        throw ValidationException::withMessages([
                            "bookings.{$index}.start_at" => [
                                "The member's package allows a maximum of {$durationHours} {$bookingData['type']} hours per day, and that limit has been reached.",
                            ],
                        ]);
                    }

                    $resourceBookingMinutesByDate[$dateKey] = $previouslyBookedMinutes + $durationMinutes;
                }

                $bookingData['end_at'] = $endAt->format('Y-m-d H:i:s');
            }

            $this->validateBooking($bookingData, $package, $data['member_id']);
        }
        unset($bookingData);

        $finalAmount = isset($data['amount']) && $data['amount'] !== null
            ? (float) $data['amount']
            : (float) $package->price;

        $couponId = $data['coupon_id'] ?? null;
        $discountAmount = 0.0;
        $coupon = null;

        $convenienceFee = isset($data['convenience_fee']) && $data['convenience_fee'] !== null
            ? round((float) $data['convenience_fee'], 2)
            : 0.0;

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

        $parentBooking = DB::transaction(function () use ($data, $package, $bookedById, $finalAmount, $couponId, $discountAmount, $convenienceFee, $invoiceId, $paymentId) {
            $booking = $this->bookingRepository->create([
                'member_id' => $data['member_id'],
                'package_id' => $package->id,
                'invoice_id' => $invoiceId,
                'payment_id' => $paymentId,
                'booking_type' => 'package',
                'status' => 'PENDING',
                'amount' => $finalAmount,
                'subtotal' => $finalAmount,
                'discount_amount' => $discountAmount,
                'convenience_fee' => $convenienceFee,
                'total_amount' => round($finalAmount - $discountAmount + $convenienceFee, 2),
                'coupon_id' => $couponId,
                'notes' => $data['notes'] ?? null,
                'booked_by_user_id' => $bookedById,
            ]);

            if ($paymentId) {
                \App\Models\Payment::where('id', $paymentId)->update(['booking_id' => $booking->id]);
            }

            return $booking;
        });

        if ($paymentId) {
            $payment = \App\Models\Payment::find($paymentId);

            if ($payment && $payment->status === 'SUCCESS') {
                $parentBooking->update([
                    'status' => 'CONFIRMED',
                    'payment_status' => 'PAID',
                    'confirmed_at' => now(),
                ]);
            }
        }

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

    protected function getLockerBookedMinutesForDate(int $memberId, Carbon $date): int
    {
        $dayStart = $date->copy()->startOfDay();
        $nextDay = $dayStart->copy()->addDay();

        return LockerAssignment::query()
            ->where('member_id', $memberId)
            ->where('status', '!=', 'cancelled')
            ->where('assigned_date', '<', $nextDay)
            ->where('expiry_date', '>', $dayStart)
            ->get(['assigned_date', 'expiry_date'])
            ->sum(function (LockerAssignment $assignment) use ($dayStart, $nextDay): int {
                $assignmentStart = $assignment->assigned_date;
                $assignmentEnd = $assignment->expiry_date;
                $countStart = $assignmentStart->gt($dayStart)
                    ? $assignmentStart
                    : $dayStart;
                $countEnd = $assignmentEnd->lt($nextDay)
                    ? $assignmentEnd
                    : $nextDay;

                return max(0, (int) $countStart->diffInMinutes($countEnd));
            });
    }

    protected function getSeatBookedMinutesForDate(int $memberId, Carbon $date): int
    {
        $dayStart = $date->copy()->startOfDay();
        $nextDay = $dayStart->copy()->addDay();

        return BookingSeat::query()
            ->where('member_id', $memberId)
            ->where('status', '!=', 'cancelled')
            ->where('start_at', '<', $nextDay)
            ->where('end_at', '>', $dayStart)
            ->get(['start_at', 'end_at'])
            ->sum(function (BookingSeat $booking) use ($dayStart, $nextDay): int {
                $bookingStart = $booking->start_at;
                $bookingEnd = $booking->end_at;
                $countStart = $bookingStart->gt($dayStart) ? $bookingStart : $dayStart;
                $countEnd = $bookingEnd->lt($nextDay) ? $bookingEnd : $nextDay;

                return max(0, (int) $countStart->diffInMinutes($countEnd));
            });
    }

    protected function expandDailyResourceBookings(array $bookings, $member): array
    {
        $expandedBookings = [];
        $bookingUntil = $member->paid_through ?: $member->membership_expiry;
        $lastDate = Carbon::parse($bookingUntil)->startOfDay();

        foreach ($bookings as $index => $bookingData) {
            if (!in_array($bookingData['type'] ?? null, ['seat', 'locker'], true)) {
                $expandedBookings[] = $bookingData;
                continue;
            }

            $firstStart = Carbon::parse($bookingData['start_at']);
            $firstDate = $firstStart->copy()->startOfDay();

            if ($firstStart->lt(Carbon::now())) {
                throw ValidationException::withMessages([
                    "bookings.{$index}.start_at" => [
                        'The booking start time must be in the future.',
                    ],
                ]);
            }

            if ($firstDate->gt($lastDate)) {
                throw ValidationException::withMessages([
                    "bookings.{$index}.start_at" => [
                        'The booking start date is after the member paid membership period.',
                    ],
                ]);
            }

            for ($date = $firstDate->copy(); $date->lte($lastDate); $date->addDay()) {
                $dailyBooking = $bookingData;
                $dailyBooking['start_at'] = $date->copy()
                    ->setTime($firstStart->hour, $firstStart->minute, $firstStart->second)
                    ->format('Y-m-d H:i:s');
                $expandedBookings[] = $dailyBooking;
            }
        }

        return $expandedBookings;
    }

    protected function createStaffBookBooking(array $data): Booking
    {
        $staff = \App\Models\Staff::query()
            ->whereKey($data['staff_id'])
            ->where('is_active', true)
            ->first();

        if (! $staff) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(
                'The selected staff borrower is inactive or was not found.'
            );
        }

        $issuedByStaffId = \App\Models\Staff::query()
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->value('id');

        $bookings = $data['bookings'] ?? [];
        if (empty($bookings) || collect($bookings)->contains(fn ($item) => ($item['type'] ?? null) !== 'book')) {
            throw new \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException(
                'Staff bookings can contain book copies only.'
            );
        }

        $ratePerBook = (float) (Setting::query()
            ->where('group', 'booking')
            ->where('key', 'staff_book_rate')
            ->value('value') ?? 0);

        if ($ratePerBook <= 0) {
            throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException(
                'Staff book rate is not configured. Set it in settings before booking.'
            );
        }

        $totalAmount = round($ratePerBook * count($bookings), 2);

        return DB::transaction(function () use ($data, $staff, $issuedByStaffId, $bookings, $ratePerBook, $totalAmount) {
            $booking = $this->bookingRepository->create([
                'staff_borrower_id' => $staff->id,
                'booked_by_user_id' => Auth::id(),
                'booking_type' => 'book',
                'status' => 'PENDING',
                'payment_status' => 'UNPAID',
                'booking_source' => 'COUNTER',
                'amount' => $totalAmount,
                'subtotal' => $totalAmount,
                'discount_amount' => 0,
                'convenience_fee' => 0,
                'total_amount' => $totalAmount,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($bookings as $bookData) {
                $copy = \App\Models\Copy::query()->lockForUpdate()->findOrFail($bookData['copy_id']);
                if ($copy->status !== 'available') {
                    throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException(
                        'Book copy '.$copy->id.' is not available.'
                    );
                }

                $this->borrowRepository->create([
                    'booking_id' => $booking->id,
                    'copy_id' => $copy->id,
                    'staff_borrower_id' => $staff->id,
                    'staff_id' => $issuedByStaffId,
                    'checkout_date' => Carbon::now(),
                    'due_date' => $data['due_date'],
                    'renewal_count' => 0,
                    'status' => 'active',
                ]);

                $this->copyRepository->update($copy->id, ['status' => 'on_loan']);
            }

            $invoice = app(InvoiceService::class)->createStaffBookInvoice(
                staffId: $staff->id,
                amount: $totalAmount,
                notes: 'Staff book booking #'.$booking->id.'; '.$ratePerBook.' per book.',
            );
            $booking->update(['invoice_id' => $invoice->id]);

            return $booking->fresh([
                'staffBorrower',
                'invoice',
                'borrows.copy.edition.book',
            ]);
        });
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
            $hours = $start->diffInMinutes($end) / 60;

            if ($hours > (float) $package->max_seat_hours_per_day) {
                throw new \Exception('Exceeds maximum allowed seat hours per day.');
            }

            $seat = $this->seatRepository->find($data['seat_id']);

            if (!$seat) {
                throw new \Exception('Seat not found.');
            }

            if (in_array($seat->status, ['maintenance', 'out_of_service'], true)) {
                throw new \Exception('Seat is not available for booking.');
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

            if (in_array($locker->status, ['maintenance', 'out_of_service'], true)) {
                throw new \Exception('Locker is not available for booking.');
            }

            if ($package->locker_type && $locker->locker_type !== $package->locker_type) {
                throw new \Exception('Locker type does not match the member package.');
            }

            $existingAssignment = LockerAssignment::where('locker_id', $data['locker_id'])
                ->where('status', '!=', 'cancelled')
                ->where('assigned_date', '<', $data['end_at'])
                ->where('expiry_date', '>', $data['start_at'])
                ->exists();

            if ($existingAssignment) {
                throw new \Exception('Locker is already booked for the selected time.');
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
        $hours = $start->diffInMinutes($end) / 60;

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

        $now = Carbon::now();
        $hasCurrentBooking = BookingSeat::query()
            ->where('seat_id', $seat->id)
            ->whereIn('status', ['booked', 'active'])
            ->where('start_at', '<=', $now)
            ->where('end_at', '>', $now)
            ->exists();

        $this->seatRepository->update($seat->id, [
            'status' => $hasCurrentBooking ? 'occupied' : 'available',
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

        $this->copyRepository->update($copy->id, [
            'status' => 'on_loan',
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
            ->where('status', '!=', 'cancelled')
            ->where('assigned_date', '<', $data['end_at'])
            ->where('expiry_date', '>', $data['start_at'])
            ->exists();

        if ($existingAssignment) {
            throw new \Exception('Locker is already booked for the selected time.');
        }

        $this->lockerAssigmentsRepository->create([
            'booking_id' => $parentBookingId,
            'locker_id' => $data['locker_id'],
            'member_id' => $memberId,
            'assigned_date' => $data['start_at'],
            'expiry_date' => $data['end_at'],
            'status' => 'active',
        ]);

        $now = Carbon::now();
        $hasCurrentAssignment = LockerAssignment::query()
            ->where('locker_id', $locker->id)
            ->where('status', 'active')
            ->where('assigned_date', '<=', $now)
            ->where('expiry_date', '>', $now)
            ->exists();

        $this->lockerRepository->update($locker->id, [
            'status' => $hasCurrentAssignment ? 'assigned' : 'available',
        ]);
    }

    public function update(int $id, array $data): Booking
    {
        $booking = $this->bookingRepository->find($id);

        if (!$booking) {
            throw new \Exception('Booking not found.');
        }

        return DB::transaction(function () use ($booking, $data) {
            if (array_key_exists('convenience_fee', $data)) {
                $fee = round((float) ($data['convenience_fee'] ?? 0), 2);
                $data['convenience_fee'] = $fee;
                $data['total_amount'] = round(
                    (float) $booking->subtotal
                    - (float) $booking->discount_amount
                    + $fee,
                    2
                );
            }

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

            if (isset($data['status']) && strtoupper($data['status']) === 'CANCELLED') {
                $this->releaseResourcesForBooking($booking);
            }

            return $updated;
        });
    }

    protected function releaseResourcesForBooking(Booking $booking): void
    {
        $booking->loadMissing('bookingSeats', 'borrows', 'lockerAssignments');

        foreach ($booking->bookingSeats as $seat) {
            if ($seat->status === 'cancelled') {
                continue;
            }
            $this->bookingSeatRepository->update($seat->id, [
                'status' => 'cancelled',
            ]);
            $this->seatRepository->update($seat->seat_id, [
                'status' => 'available',
            ]);
        }

        foreach ($booking->lockerAssignments as $assignment) {
            if ($assignment->status === 'cancelled') {
                continue;
            }
            $this->lockerAssigmentsRepository->update($assignment->id, [
                'status' => 'cancelled',
            ]);
            $this->lockerRepository->update($assignment->locker_id, [
                'status' => 'available',
            ]);
        }

        foreach ($booking->borrows as $borrow) {
            if (in_array($borrow->status, ['returned', 'lost'], true)) {
                continue;
            }
            $this->borrowRepository->delete($borrow->id);
            if ($borrow->copy_id) {
                $this->copyRepository->update($borrow->copy_id, [
                    'status' => 'available',
                ]);
            }
        }
    }

    public function delete(int $id): bool
    {
        $booking = $this->bookingRepository->find($id);

        if (!$booking) {
            throw new \Exception('Booking not found.');
        }

        return DB::transaction(function () use ($booking) {
            $this->releaseResourcesForBooking($booking);

            return $this->bookingRepository->delete($booking->id);
        });
    }

    public function getAllSeatBookings(array $filters = []): array
    {
        return $this->bookingSeatRepository->getAll($filters);
    }

    public function getSeatBookingById(int $id): ?\App\Models\BookingSeat
    {
        return $this->bookingSeatRepository->find($id);
    }

    public function completeSeatBooking(int $id): \App\Models\BookingSeat
    {
        $seat = $this->bookingSeatRepository->find($id);

        if (!$seat) {
            throw new \Exception('Seat booking not found.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($seat) {
            $updated = $this->bookingSeatRepository->update($seat->id, [
                'status' => 'completed',
            ]);

            return $updated->fresh();
        });
    }

    public function getSeatBookingsByBooking(int $bookingId)
    {
        $this->ensureBookingExists($bookingId);

        return $this->bookingSeatRepository->byBooking($bookingId);
    }

    public function getBorrowsByBooking(int $bookingId)
    {
        $this->ensureBookingExists($bookingId);

        return $this->borrowRepository->byBooking($bookingId);
    }

    public function getLockerAssignmentsByBooking(int $bookingId)
    {
        $this->ensureBookingExists($bookingId);

        return $this->lockerAssigmentsRepository->byBooking($bookingId);
    }

    protected function ensureBookingExists(int $bookingId): void
    {
        if (!$this->bookingRepository->find($bookingId)) {
            throw new \Exception('Booking not found.');
        }
    }
}
