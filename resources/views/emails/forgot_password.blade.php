<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin password reset</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2f7;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2f7;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #d9e2ef;">
                    <tr>
                        <td style="background-color:#1a5edb;padding:28px 32px;text-align:center;">
                            <p style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:0.4px;">EmpowerEd</p>
                            <p style="margin:8px 0 0;color:#d6e4ff;font-size:14px;">Admin panel security</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 12px;color:#1c2a3a;font-size:18px;font-weight:700;">Reset your admin password</p>
                            <p style="margin:0 0 16px;color:#4a5b6d;font-size:15px;line-height:1.6;">
                                Dear {{ e($name ?? 'Admin') }},
                            </p>
                            <p style="margin:0 0 20px;color:#4a5b6d;font-size:15px;line-height:1.6;">
                                Use the authorization code below to continue a password reset on the EmpowerEd admin website
                                @if (!empty($email))
                                    for <strong>{{ e($email) }}</strong>
                                @endif
                                .
                            </p>
                            @if (!empty($code) || !empty($otp))
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:8px 0 20px;">
                                        <div style="display:inline-block;background-color:#f4f7fb;border:1px dashed #1a5edb;border-radius:10px;padding:16px 28px;">
                                            <span style="display:block;font-size:12px;color:#6b7c8d;letter-spacing:1px;text-transform:uppercase;margin-bottom:8px;">Code</span>
                                            <span style="font-size:32px;letter-spacing:10px;font-weight:700;color:#1a5edb;font-family:'Courier New',Courier,monospace;">{{ e($code ?? $otp) }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            @if (!empty($reset_url))
                            <p style="margin:0 0 24px;text-align:center;">
                                <a href="{{ $reset_url }}" style="display:inline-block;background-color:#1a5edb;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:8px;font-size:15px;font-weight:700;">Reset password</a>
                            </p>
                            @elseif (!empty($link))
                            <p style="margin:0 0 24px;text-align:center;">{!! $link !!}</p>
                            @endif
                            <p style="margin:0 0 12px;color:#4a5b6d;font-size:14px;line-height:1.6;">
                                This code expires in 10 minutes. If you did not request a reset, you can ignore this email.
                            </p>
                            <p style="margin:0;color:#8a97a6;font-size:13px;line-height:1.6;">
                                If you have any questions, please contact the EmpowerEd team.
                            </p>
                            <p style="margin:20px 0 0;color:#1c2a3a;font-size:14px;line-height:1.6;">
                                Best regards,<br>
                                <strong>EmpowerED Team</strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f7f9fc;padding:16px 32px;text-align:center;border-top:1px solid #e6edf5;">
                            <p style="margin:0;color:#8a97a6;font-size:12px;">
                                &copy; {{ e($year ?? date('Y')) }} EmpowerEd Child Healthcare. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
