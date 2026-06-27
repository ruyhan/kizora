@php
    // Swap the logo placeholder for an inline CID image (renders in Gmail too).
    // Falls back to the base64/URL src when no local file is available.
    $out = $html;
    if (str_contains($out, '__KZ_LOGO_SRC__')) {
        $src = (! empty($logoPath) && is_file($logoPath))
            ? $message->embed($logoPath)
            : ($logoFallback ?? '');
        $out = str_replace('__KZ_LOGO_SRC__', $src, $out);
    }
@endphp
{!! $out !!}
