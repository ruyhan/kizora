<?php

namespace App\Http\Middleware;

use App\Support\Phone;
use Closure;
use Illuminate\Http\Request;

/**
 * Stores every phone number in one format (+8801712345678), whatever the customer
 * typed. Numbers that aren't valid are left untouched so validation can report
 * them back exactly as they were entered.
 */
class NormalizePhone
{
    private const FIELDS = ['phone', 'guest_phone', 'customer_phone'];

    public function handle(Request $request, Closure $next)
    {
        $replace = [];
        foreach (self::FIELDS as $field) {
            $value = $request->input($field);
            if (is_string($value) && ($normalized = Phone::normalize($value))) {
                $replace[$field] = $normalized;
            }
        }
        if ($replace) {
            $request->merge($replace);
        }

        return $next($request);
    }
}
