<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class AdminOtpEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        EmailTemplate::updateOrCreate(
            [
                'variable_name' => 'admin_otp',
                'language' => 'english',
            ],
            [
                'subject' => 'Your Empowered Health admin verification code',
                'variables' => '{name},{email},{otp},{year}',
                'active' => 1,
                'description' => <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2f7;padding:32px 12px;font-family:Arial,Helvetica,sans-serif;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #d9e2ef;">
        <tr>
          <td style="background-color:#1a5edb;padding:28px 32px;text-align:center;">
            <p style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">EmpowerEd</p>
            <p style="margin:8px 0 0;color:#d6e4ff;font-size:14px;">Admin panel security</p>
          </td>
        </tr>
        <tr>
          <td style="padding:32px;">
            <p style="margin:0 0 12px;color:#1c2a3a;font-size:18px;font-weight:700;">Your login verification code</p>
            <p style="margin:0 0 20px;color:#4a5b6d;font-size:15px;line-height:1.6;">Hello {name}, someone is signing in to the EmpowerEd admin panel with <strong>{email}</strong>. Use this one-time code to continue.</p>
            <p style="margin:0 0 24px;text-align:center;">
              <span style="display:inline-block;background-color:#f4f7fb;border:1px dashed #1a5edb;border-radius:10px;padding:16px 28px;font-size:32px;letter-spacing:10px;font-weight:700;color:#1a5edb;font-family:'Courier New',Courier,monospace;">{otp}</span>
            </p>
            <p style="margin:0 0 12px;color:#4a5b6d;font-size:14px;line-height:1.6;">This code expires shortly and can be used only once. Do not share it with anyone.</p>
            <p style="margin:0;color:#8a97a6;font-size:13px;">If you did not try to log in, ignore this email and contact the EmpowerEd team.</p>
          </td>
        </tr>
        <tr>
          <td style="background-color:#f7f9fc;padding:16px 32px;text-align:center;border-top:1px solid #e6edf5;">
            <p style="margin:0;color:#8a97a6;font-size:12px;">&copy; {year} EmpowerEd Child Healthcare. All rights reserved.</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML,
            ]
        );

        EmailTemplate::updateOrCreate(
            [
                'variable_name' => 'forgot_password',
                'language' => 'english',
            ],
            [
                'subject' => 'Reset your EmpowerEd admin password',
                'variables' => '{name},{email},{code},{otp},{year},{link}',
                'active' => 1,
                'description' => <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2f7;padding:32px 12px;font-family:Arial,Helvetica,sans-serif;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #d9e2ef;">
        <tr>
          <td style="background-color:#1a5edb;padding:28px 32px;text-align:center;">
            <p style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">EmpowerEd</p>
            <p style="margin:8px 0 0;color:#d6e4ff;font-size:14px;">Admin panel security</p>
          </td>
        </tr>
        <tr>
          <td style="padding:32px;">
            <p style="margin:0 0 12px;color:#1c2a3a;font-size:18px;font-weight:700;">Reset your admin password</p>
            <p style="margin:0 0 16px;color:#4a5b6d;font-size:15px;line-height:1.6;">Dear {name},</p>
            <p style="margin:0 0 20px;color:#4a5b6d;font-size:15px;line-height:1.6;">Use the authorization code below to continue a password reset on the EmpowerEd admin website for <strong>{email}</strong>.</p>
            <p style="margin:0 0 24px;text-align:center;">
              <span style="display:inline-block;background-color:#f4f7fb;border:1px dashed #1a5edb;border-radius:10px;padding:16px 28px;font-size:32px;letter-spacing:10px;font-weight:700;color:#1a5edb;font-family:'Courier New',Courier,monospace;">{code}</span>
            </p>
            <p style="margin:0 0 24px;text-align:center;">{link}</p>
            <p style="margin:0 0 12px;color:#4a5b6d;font-size:14px;">This code expires in 10 minutes. If you did not request a reset, you can ignore this email.</p>
            <p style="margin:0;color:#8a97a6;font-size:13px;">If you have any questions, please contact the EmpowerEd team.</p>
            <p style="margin:20px 0 0;color:#1c2a3a;font-size:14px;">Best regards,<br><strong>EmpowerED Team</strong></p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML,
            ]
        );
    }
}
