<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->date('paid_through')->nullable()->after('membership_expiry');
            $table->unsignedBigInteger('membership_invoice_id')->nullable()->after('paid_through');
            $table->unsignedSmallInteger('membership_paid_days')->default(0)->after('membership_invoice_id');
        });

        DB::table('members')->update([
            'paid_through' => DB::raw('membership_expiry'),
        ]);

        $today = Carbon::today();

        DB::table('members')->orderBy('id')->chunkById(100, function ($members) use ($today): void {
            foreach ($members as $member) {
                $invoice = DB::table('invoices')
                    ->where('member_id', $member->id)
                    ->whereIn('status', ['partially_paid', 'overdue', 'paid'])
                    ->orderByDesc('id')
                    ->first();

                if (! $invoice || (float) $invoice->paid_amount <= 0) {
                    continue;
                }

                $firstPayment = DB::table('payments')
                    ->where('invoice_id', $invoice->id)
                    ->where('status', 'SUCCESS')
                    ->orderByRaw('COALESCE(paid_at, payment_date) ASC')
                    ->first();

                $package = DB::table('packages')->where('id', $member->package_id)->first();

                if (! $firstPayment || ! $package) {
                    continue;
                }

                $startDate = $invoice->status === 'paid' && $member->membership_start
                    ? $member->membership_start
                    : ($firstPayment->paid_at ?? $firstPayment->payment_date);
                $start = Carbon::parse($startDate)->startOfDay();
                $expiry = match ($package->duration_unit ?? 'month') {
                    'day' => $start->copy()->addDays((int) $package->duration),
                    'year' => $start->copy()->addYears((int) $package->duration),
                    default => $start->copy()->addMonths((int) $package->duration),
                };
                $fullDurationDays = max(1, $start->diffInDays($expiry));
                $fraction = $invoice->status === 'paid'
                    ? 1
                    : min(1, (float) $invoice->paid_amount / max(0.01, (float) $package->price));
                $paidDays = max(1, (int) ceil($fullDurationDays * $fraction));
                $paidThrough = $fraction >= 1
                    ? $expiry
                    : $start->copy()->addDays($paidDays - 1);
                if ($paidThrough->gt($expiry)) {
                    $paidThrough = $expiry->copy();
                }

                $status = $expiry->lt($today)
                    ? 'expired'
                    : ($paidThrough->lt($today) ? 'suspended' : 'active');

                if ($member->status === 'cancelled') {
                    $status = 'cancelled';
                } elseif (
                    $member->status === 'suspended'
                    && $invoice->status === 'paid'
                    && $expiry->gte($today)
                ) {
                    $status = 'suspended';
                }

                DB::table('members')->where('id', $member->id)->update([
                    'membership_start' => $start->toDateString(),
                    'membership_expiry' => $expiry->toDateString(),
                    'paid_through' => $paidThrough->toDateString(),
                    'membership_invoice_id' => $invoice->id,
                    'membership_paid_days' => $paidDays,
                    'status' => $status,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropColumn(['paid_through', 'membership_invoice_id', 'membership_paid_days']);
        });
    }
};
