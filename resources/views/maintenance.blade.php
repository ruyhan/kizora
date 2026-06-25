<?php
    $name     = $s['site_name'] ?? 'Our store';
    $title    = $s['maintenance_title'] ?? 'We’ll be right back';
    $message  = $s['maintenance_message'] ?? 'We’re briefly down for scheduled maintenance and will be back online shortly. Thanks for your patience.';
    $favicon  = $s['favicon'] ?? null;
    $logoLight = $s['logo_light'] ?? null;
    $logoMain  = $s['logo'] ?? null;
    $logo      = $logoLight ?: $logoMain;
    $invert    = ! $logoLight && $logoMain;   // CSS-invert the dark wordmark onto the dark bg
    $email     = $s['email'] ?? null;
    $facebook  = $s['facebook'] ?? null;
    $instagram = $s['instagram'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · {{ $name }}</title>
    @if($favicon)<link rel="icon" href="{{ $favicon }}">@endif
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --ink:#17181B; --ink-soft:#1F2024; --accent:#ED1C24;
            --line:rgba(255,255,255,.10); --muted:rgba(255,255,255,.62);
        }
        html,body{height:100%;width:100%;max-width:100%;overflow-x:hidden}
        html{-webkit-text-size-adjust:100%}
        body{
            font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
            background:var(--ink); color:#fff; min-height:100%;
            display:flex; align-items:center; justify-content:center;
            position:relative; overflow:hidden; padding:24px;
            -webkit-font-smoothing:antialiased;
        }
        /* decorative ambience */
        .blob{position:absolute;border-radius:50%;filter:blur(90px);pointer-events:none;z-index:0}
        .blob.a{width:520px;height:520px;top:-200px;left:-160px;background:rgba(255,255,255,.05)}
        .blob.b{width:560px;height:560px;bottom:-240px;right:-180px;background:rgba(237,28,36,.16)}
        .grid{position:absolute;inset:0;z-index:0;opacity:.05;
            background-image:radial-gradient(circle, #fff 1.4px, transparent 1.4px);
            background-size:30px 30px}
        .wrap{position:relative;z-index:1;width:100%;max-width:560px;text-align:center;overflow-wrap:break-word}
        .logo{height:40px;width:auto;object-fit:contain;margin:0 auto 36px;display:block;opacity:.96}
        .logo.invert{filter:brightness(0) invert(1)}
        .wordmark{font-size:24px;font-weight:800;letter-spacing:-.02em;margin-bottom:36px}
        /* status badge */
        .badge{display:inline-flex;align-items:center;gap:9px;
            padding:7px 15px;border-radius:999px;
            background:rgba(255,255,255,.06);border:1px solid var(--line);
            font-size:12px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;
            color:rgba(255,255,255,.85);margin-bottom:26px}
        .dot{width:9px;height:9px;border-radius:50%;background:var(--accent);position:relative}
        .dot::after{content:"";position:absolute;inset:0;border-radius:50%;background:var(--accent);
            animation:ping 1.6s cubic-bezier(0,0,.2,1) infinite}
        @keyframes ping{0%{transform:scale(1);opacity:.7}80%,100%{transform:scale(2.6);opacity:0}}
        h1{font-size:clamp(28px,6vw,46px);line-height:1.1;font-weight:800;letter-spacing:-.03em;margin-bottom:18px;overflow-wrap:break-word}
        p.msg{font-size:16px;line-height:1.7;color:var(--muted);max-width:460px;margin:0 auto;overflow-wrap:break-word}
        /* progress shimmer */
        .bar{height:5px;width:200px;border-radius:999px;background:rgba(255,255,255,.10);
            overflow:hidden;margin:34px auto 0;position:relative}
        .bar::after{content:"";position:absolute;top:0;left:0;height:100%;width:42%;border-radius:999px;
            background:linear-gradient(90deg,transparent,var(--accent),transparent);
            animation:slide 1.8s ease-in-out infinite}
        @keyframes slide{0%{left:-45%}100%{left:105%}}
        /* footer */
        .foot{margin-top:46px;display:flex;flex-direction:column;align-items:center;gap:18px}
        .mail{color:#fff;text-decoration:none;font-size:14px;font-weight:600;
            border-bottom:1px solid rgba(255,255,255,.25);padding-bottom:2px;transition:border-color .2s}
        .mail:hover{border-color:#fff}
        .social{display:flex;gap:12px}
        .social a{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;
            background:rgba(255,255,255,.06);border:1px solid var(--line);color:rgba(255,255,255,.7);
            transition:all .2s}
        .social a:hover{background:#fff;color:var(--ink);transform:translateY(-2px)}
        .social svg{width:18px;height:18px}
        .copy{margin-top:30px;font-size:12px;color:rgba(255,255,255,.35)}
    </style>
</head>
<body>
    <span class="blob a"></span>
    <span class="blob b"></span>
    <span class="grid"></span>

    <main class="wrap">
        @if($logo)
            <img src="{{ $logo }}" alt="{{ $name }}" class="logo{{ $invert ? ' invert' : '' }}">
        @else
            <div class="wordmark">{{ $name }}</div>
        @endif

        <div class="badge"><span class="dot"></span> Scheduled maintenance</div>

        <h1>{{ $title }}</h1>
        <p class="msg">{{ $message }}</p>

        <div class="bar"></div>

        <div class="foot">
            @if($email)
                <a class="mail" href="mailto:{{ $email }}">{{ $email }}</a>
            @endif

            @if($facebook || $instagram)
                <div class="social">
                    @if($facebook)
                        <a href="{{ $facebook }}" target="_blank" rel="noopener" aria-label="Facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                    @endif
                    @if($instagram)
                        <a href="{{ $instagram }}" target="_blank" rel="noopener" aria-label="Instagram">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                    @endif
                </div>
            @endif

            <p class="copy">© {{ date('Y') }} {{ $name }}</p>
        </div>
    </main>
</body>
</html>
