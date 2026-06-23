<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kizora invitation</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #ececef;">
                    <tr>
                        <td style="background:#17181B;padding:24px 28px;">
                            <span style="color:#ffffff;font-size:22px;font-weight:800;letter-spacing:-0.5px;">Kizora</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 28px;color:#17181B;">
                            <h1 style="margin:0 0 12px;font-size:20px;">You're invited to the team</h1>
                            <p style="margin:0 0 16px;color:#52525b;font-size:14px;line-height:1.6;">
                                {{ $inviter ? $inviter . ' has invited you' : 'You have been invited' }}
                                to join the Kizora admin team{{ $role ? ' as ' . $role : '' }}.
                                Click below to set your password and activate your account.
                            </p>
                            <p style="margin:24px 0;">
                                <a href="{{ $url }}" style="display:inline-block;background:#17181B;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;padding:12px 24px;border-radius:10px;">
                                    Accept invitation
                                </a>
                            </p>
                            <p style="margin:0 0 4px;color:#a1a1aa;font-size:12px;">Or paste this link into your browser:</p>
                            <p style="margin:0;color:#6366f1;font-size:12px;word-break:break-all;">{{ $url }}</p>
                            <p style="margin:24px 0 0;color:#a1a1aa;font-size:12px;">This invitation expires in 7 days. If you weren't expecting it, you can ignore this email.</p>
                        </td>
                    </tr>
                </table>
                <p style="color:#a1a1aa;font-size:11px;margin:16px 0 0;">© {{ date('Y') }} Kizora</p>
            </td>
        </tr>
    </table>
</body>
</html>
