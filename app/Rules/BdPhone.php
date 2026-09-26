<?php

namespace App\Rules;

use App\Support\Phone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Valid Bangladeshi mobile number (11 digits locally). Store the value through Phone::normalize(). */
class BdPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Phone::isValid(is_string($value) ? $value : null)) {
            $fail(Phone::MESSAGE);
        }
    }
}
