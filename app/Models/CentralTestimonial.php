<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentralTestimonial extends Model
{
    protected $fillable = [
        'name', 'role_or_company', 'quote', 'photo', 'rating', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
