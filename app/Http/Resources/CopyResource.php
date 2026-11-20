<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CopyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'book_id' => $this->edition?->book?->id,
            'edition_id' => $this->edition_id,
            'book_name' => $this->edition?->book?->title,
            'barcode' => $this->barcode,
            'shelf_location' => $this->shelf_location,
            'condition' => $this->condition,
            'status' => $this->status,
            'current_borrow' => $this->currentBorrow(),
            'acquisition_date' => $this->acquisition_date?->format('Y-m-d'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function currentBorrow(): ?array
    {
        if ($this->status !== 'on_loan' || ! $this->resource->relationLoaded('currentBorrows')) {
            return null;
        }

        $borrow = $this->resource->getRelation('currentBorrows')
            ->sortByDesc('id')
            ->first();

        if (! $borrow) {
            return null;
        }

        $member = $borrow->member;
        $staffBorrower = $borrow->staffBorrower;

        return [
            'id' => $borrow->id,
            'status' => $borrow->status,
            'member_id' => $borrow->member_id,
            'member_name' => $member
                ? trim($member->first_name.' '.$member->last_name)
                : null,
            'staff_id' => $borrow->staff_borrower_id,
            'staff_name' => $staffBorrower
                ? trim($staffBorrower->first_name.' '.$staffBorrower->last_name)
                : null,
            'checkout_date' => $borrow->checkout_date?->format('Y-m-d H:i:s'),
            'due_date' => $borrow->due_date?->format('Y-m-d'),
        ];
    }
}
