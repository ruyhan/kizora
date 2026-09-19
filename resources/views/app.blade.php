<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Kizora') }}</title>

    <meta name="theme-color" content="#17181B">

    <!-- PWA -->
    <link rel="manifest" href="/build/manifest.webmanifest">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Kizora">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Site settings (favicon + tracking scripts are read from here) -->
    @php($kzSettings = app(\App\Support\PageContent::class)->get('settings'))
    @php($kzFavicon = data_get($kzSettings, 'favicon'))
    @if($kzFavicon)
        <link rel="icon" href="{{ $kzFavicon }}">
        <link rel="apple-touch-icon" href="{{ $kzFavicon }}">
    @else
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon.ico">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    @endif

    <!-- Open Graph -->
    <meta property="og:title" content="{{ config('app.name', 'Kizora') }}">
    <meta property="og:description" content="Kizora — premium backpacks, water bottles, flasks & lifestyle gear designed to move with you.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">

    {{-- Tracking scripts (Admin → Settings): GA4 / GTM / Meta Pixel etc. --}}
    {!! data_get($kzSettings, 'head_scripts') !!}

    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="font-body antialiased bg-white text-charcoal">
    @inertia

    {{-- Tracking scripts placed before </body> (Admin → Settings) --}}
    {!! data_get($kzSettings, 'body_scripts') !!}
</body>
</html>
