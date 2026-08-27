<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'package_id' => $this->package_id,

            'package' => $this->whenLoaded(
                'package',
                fn () => new PackageResource($this->package)
            ),

            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'membership_start' => $this->membership_start?->format('Y-m-d'),
            'membership_expiry' => $this->membership_expiry?->format('Y-m-d'),
            'status' => $this->status,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}