<?php

namespace App\Support;

/**
 * Bangladeshi mobile numbers. Accepts what people actually type —
 * 01712345678, 8801712345678, +880 1712-345678, +880 01712345678 — and
 * normalises to E.164 (+8801712345678), the format the rest of the app stores.
 * A valid number is 11 digits locally: 01 + operator digit 3–9 + 8 digits.
 */
class Phone
{
    public const MESSAGE = 'Please enter a valid 11-digit Bangladeshi mobile number (e.g. 01712345678).';

    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 3);
        }
        if (! str_starts_with($digits, '0')) {
            $digits = '0' . $digits;          // "1712345678" after the country code
        }

        return preg_match('/^01[3-9]\d{8}$/', $digits) ? '+88' . $digits : null;
    }

    public static function isValid(?string $value): bool
    {
        return self::normalize($value) !== null;
    }
}
