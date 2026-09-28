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
class CentralSetting extends Model
{
    /**
     * Central settings use the same table structure as the tenant
     * settings table, but they always live in the central database.
     */
    protected $table = 'settings';

    public function casts(): array
    {
        return [
            'type' => 'string',
            'is_locked' => 'boolean',
        ];
    }

    /**
     * Always read and write through the central connection, even when a
     * tenant has been initialized and the default connection is switched.
     */
    public function getConnectionName()
    {
        return config('tenancy.database.central_connection')
            ?? config('database.default');
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
