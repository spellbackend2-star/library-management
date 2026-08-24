<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublisherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'address' => $this->address,
            'website' => $this->website,
            'contact_email' => $this->contact_email,

            'created_at' => $this->created_at,
        ];
    }
}
