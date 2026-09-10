<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'group',
    'key',
    'value',
    'type',
    'description',
    'is_locked',
])]
class Setting extends Model
{
    public function casts(): array
    {
        return [
            'type' => 'string',
            'is_locked' => 'boolean',
        ];
    }

    protected function value(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value === null) {
                    return null;
                }

                return match ($this->attributes['type'] ?? 'string') {
                    'integer' => (int) $value,
                    'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                    'json' => json_decode($value, true),
                    'text', 'string' => (string) $value,
                    default => $value,
                };
            },
            set: function ($value) {
                if ($value === null) {
                    return null;
                }

                if (is_array($value)) {
                    return json_encode($value);
                }

                return (string) $value;
            },
        );
    }

    public function scopeOfGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }
}
