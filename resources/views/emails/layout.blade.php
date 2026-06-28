<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    {{-- Declare dark-mode support so clients render OUR intentional dark theme instead of auto-inverting. --}}
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ $subject ?? ($brand['company_name'] ?? 'Email') }}</title>
    <style>
        :root { color-scheme: light dark; supported-color-schemes: light dark; }
        body { margin:0; padding:0; background:#f4f4f5; -webkit-text-size-adjust:100%; }
        table { border-collapse:collapse; }
        img { border:0; outline:none; text-decoration:none; -ms-interpolation-mode:bicubic; }
        a { color:#17181B; }
        @media only screen and (max-width:620px) {
            .kz-container { width:100% !important; }
            .kz-pad { padding-left:20px !important; padding-right:20px !important; }
            .kz-stack { display:block !important; width:100% !important; padding:0 0 14px 0 !important; }
        }
        /* Intentional dark theme: dark page, header stays dark, the content card keeps its
           designed colours so the email never looks "inverted" or broken. */
        @media (prefers-color-scheme: dark) {
            body, .kz-bg { background:#0e0f11 !important; }
            .kz-container { background:#ffffff !important; border-color:#2a2b30 !important; }
            .kz-header { background:#17181B !important; }
            .kz-footer { background:#fafafa !important; }
        }
        /* Outlook dark mode rewrites elements with data-ogsb/data-ogsc — apply the same theme. */
        [data-ogsb] .kz-bg { background:#0e0f11 !important; }
        [data-ogsb] .kz-container { background:#ffffff !important; }
        [data-ogsb] .kz-header { background:#17181B !important; }
        [data-ogsb] .kz-footer { background:#fafafa !important; }
    </style>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <span style="display:none;font-size:1px;color:#f4f4f5;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ $subject ?? '' }}</span>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="kz-bg" style="background:#f4f4f5;">
        <tr>
            <td align="center" style="padding:28px 12px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" class="kz-container" style="width:600px;max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e7e7ea;">

                    {{-- Header --}}
                    <tr>
                        @if(!empty($brand['header_badge']))
                            {{-- Logo pre-baked onto a dark image: stays dark + crisp even under mobile dark-mode inversion. --}}
                            <td class="kz-header" style="background:#17181B;padding:0;font-size:0;line-height:0;mso-line-height-rule:exactly;">
                                <img src="{{ $brand['logo'] }}" alt="{{ $brand['company_name'] ?? '' }}" width="600" style="display:block;width:100%;max-width:600px;height:auto;border:0;">
                            </td>
                        @else
                            <td class="kz-header" style="background:#17181B;padding:26px 32px;text-align:center;">
                                @if(!empty($brand['logo']))
                                    <img src="{{ $brand['logo'] }}" alt="{{ $brand['company_name'] ?? '' }}" height="40" style="height:40px;width:auto;max-width:220px;display:inline-block;">
                                @else
                                    <span style="color:#ffffff;font-size:22px;font-weight:800;letter-spacing:-.02em;">{{ $brand['company_name'] ?? 'Kizora' }}</span>
                                @endif
                            </td>
                        @endif
                    </tr>

                    @if(!empty($brand['banner']))
                    <tr>
                        <td style="padding:0;">
                            <img src="{{ $brand['banner'] }}" alt="" width="600" style="width:100%;max-width:600px;display:block;">
                        </td>
                    </tr>
                    @endif

                    {{-- Body --}}
                    <tr>
                        <td class="kz-pad" style="padding:32px;">
                            {!! $content !!}
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td class="kz-pad kz-footer" style="padding:24px 32px;background:#fafafa;border-top:1px solid #eeeeee;">
                            <p style="margin:0 0 6px;font-size:14px;font-weight:700;color:#17181B;">{{ $brand['company_name'] ?? '' }}</p>
                            @if(!empty($brand['address']))
                                <p style="margin:0 0 4px;font-size:12px;color:#9b9ba3;line-height:1.5;">{{ $brand['address'] }}</p>
                            @endif
                            <p style="margin:0 0 12px;font-size:12px;color:#9b9ba3;">
                                @if(!empty($brand['phone']))<span>{{ $brand['phone'] }}</span>@endif
                                @if(!empty($brand['support_email'])) &nbsp;·&nbsp; <a href="mailto:{{ $brand['support_email'] }}" style="color:#9b9ba3;">{{ $brand['support_email'] }}</a>@endif
                                @if(!empty($brand['website'])) &nbsp;·&nbsp; <a href="{{ $brand['website'] }}" style="color:#9b9ba3;">{{ preg_replace('#^https?://#', '', $brand['website']) }}</a>@endif
                            </p>

                            @if(!empty($brand['socials']) && is_array($brand['socials']))
                                <p style="margin:0 0 12px;">
                                    @foreach($brand['socials'] as $name => $url)
                                        @if($url)<a href="{{ $url }}" style="display:inline-block;margin-right:10px;font-size:12px;color:#52525b;text-transform:capitalize;text-decoration:underline;">{{ $name }}</a>@endif
                                    @endforeach
                                </p>
                            @endif

                            @if(!empty($brand['footer_disclaimer']))
                                <p style="margin:0 0 6px;font-size:11px;color:#b4b4bb;line-height:1.5;">{{ $brand['footer_disclaimer'] }}</p>
                            @endif
                            <p style="margin:0;font-size:11px;color:#b4b4bb;">{{ $brand['footer_copyright'] ?? '' }}</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
