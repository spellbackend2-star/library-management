<?php

namespace App\Http\Resources;

use App\Services\CentralSettingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class SubscriptionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'duration' => $this->duration,
            'duration_unit' => $this->duration_unit,
            'is_active' => $this->is_active,
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }

    /**
     * Format a timestamp using the platform date format, which
     * defaults to "Y-m-d" so only the date part is returned.
     */
    protected function formatDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $format = app(CentralSettingService::class)->get('date_format', 'Y-m-d');

        return Carbon::parse($value)->format($format ?: 'Y-m-d');
    }
}
