<?php

namespace App\Repositories\Eloquent;

use App\Models\Member;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\MemberInterface;
use Illuminate\Database\Eloquent\Builder;

class MemberRepository extends BaseRepository implements MemberInterface
{
    protected array $allowedSorts = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'package_id',
        'status',
        'gender',
        'membership_start',
        'membership_expiry',
        'created_at',
        'updated_at',
    ];

    public function all(array $filters = [])
    {
        $query = Member::query()
            ->with('package');

        $this->applyListFilters($query, $filters);

        return $query->latest()->get();
    }

    public function getAll(array $filters = [])
    {
        $query = Member::query()
            ->with('package');

        $this->applyListFilters($query, $filters);

        /*
        |--------------------------------------------------------------------------
        | Sorting + Pagination
        |--------------------------------------------------------------------------
        */
        return $this->getPaginated(
            $query,
            $filters
        );
    }

    private function applyListFilters(Builder $query, array $filters): void
    {
        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if (
            isset($filters['search'])
            && $filters['search'] !== ''
        ) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where(
                    'first_name',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'last_name',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'email',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'phone',
                    'like',
                    '%' . $search . '%'
                );

                if (is_numeric($search)) {
                    $q->orWhere('id', $search);
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */
        if (
            isset($filters['status'])
            && $filters['status'] !== ''
        ) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Package
        |--------------------------------------------------------------------------
        */
        if (
            array_key_exists('package_id', $filters)
            && $filters['package_id'] !== null
            && $filters['package_id'] !== ''
        ) {
            $query->where(
                'package_id',
                $filters['package_id']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Gender
        |--------------------------------------------------------------------------
        */
        if (
            isset($filters['gender'])
            && $filters['gender'] !== ''
        ) {
            $query->where(
                'gender',
                $filters['gender']
            );
        }

        $this->applyPaidInvoiceFilter($query, $filters);
    }

    private function applyPaidInvoiceFilter(Builder $query, array $filters): void
    {
        if (
            ! array_key_exists('has_paid_invoice', $filters)
            || $filters['has_paid_invoice'] === null
            || $filters['has_paid_invoice'] === ''
        ) {
            return;
        }

        $hasPaidInvoice = filter_var(
            $filters['has_paid_invoice'],
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($hasPaidInvoice === null) {
            return;
        }

        if ($hasPaidInvoice) {
            $query->whereHas('invoices', function ($invoiceQuery) {
                $invoiceQuery->where('paid_amount', '>', 0);
            });

            return;
        }

        $query->whereDoesntHave('invoices', function ($invoiceQuery) {
            $invoiceQuery->where('paid_amount', '>', 0);
        });
    }

    public function find(int $id): ?Member
    {
        return Member::with('package')
            ->find($id);
    }

    public function create(array $data): Member
    {
        return Member::create($data);
    }

    public function update(
        int $id,
        array $data
    ): Member {
        $member = Member::findOrFail($id);

        $member->update($data);

        return $member->fresh('package');
    }

    public function delete(int $id): bool
    {
        return Member::findOrFail($id)
            ->delete();
    }
}
