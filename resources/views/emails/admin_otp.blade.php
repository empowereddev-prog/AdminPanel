<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin verification code</title>
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
                            <p style="margin:0 0 12px;color:#1c2a3a;font-size:18px;font-weight:700;">Your login verification code</p>
                            <p style="margin:0 0 20px;color:#4a5b6d;font-size:15px;line-height:1.6;">
                                Hello{{ !empty($name) ? ' ' . e($name) : '' }},
                                someone is signing in to the EmpowerEd admin panel
                                @if (!empty($email))
                                    with <strong>{{ e($email) }}</strong>
                                @endif
                                . Use this one-time code to continue.
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:12px 0 24px;">
                                        <div style="display:inline-block;background-color:#f4f7fb;border:1px dashed #1a5edb;border-radius:10px;padding:16px 28px;">
                                            <span style="font-size:32px;letter-spacing:10px;font-weight:700;color:#1a5edb;font-family:'Courier New',Courier,monospace;">{{ e($otp ?? '') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 12px;color:#4a5b6d;font-size:14px;line-height:1.6;">
                                This code expires shortly and can be used only once. Do not share it with anyone.
                            </p>
                            <p style="margin:0;color:#8a97a6;font-size:13px;line-height:1.6;">
                                If you did not try to log in, ignore this email and contact the EmpowerEd team.
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
