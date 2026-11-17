<?php

namespace App\Rules;

use App\Support\Isbn;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidIsbn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Isbn::isValid($value)) {
            $fail('The :attribute must be a valid 10-digit ISBN-10 or 13-digit ISBN-13 starting with 978 or 979, with a valid checksum.');
        }
    }
}
