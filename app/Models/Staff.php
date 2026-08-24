<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'first_name',
    'last_name',
    'email',
    'hire_date',
    'is_active',
])]
class Staff extends Model
{
    public $timestamps = false;

    protected $casts = [
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

     public function user()
    {
        return $this->belongsTo(User::class);
    }
}
