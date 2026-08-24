<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'address',
    'website',
    'contact_email',
])]
class Publisher extends Model
{
    public $timestamps = false;

    public function bookEditions(): HasMany
    {
        return $this->hasMany(BookEdition::class);
    }
}