<?php

namespace Database\Seeders\Emails;

/**
 * The shared chrome for EmpowerEd transactional email.
 *
 * ___mail_sender renders whatever sits in email_templates.description through
 * emails.default, which is a bare {!! $body !!} - so each row has to carry a
 * complete, self-contained HTML document. Rather than copy-pasting a table
 * skeleton into six seeders, they each supply their inner content and this
 * builds the wrapper.
 *
 * Deliberately table-based with inline styles and no <style> block, because
 * Gmail strips head styles and Outlook needs the tables. It reproduces the
 * palette already established by AdminOtpEmailTemplateSeeder so the new mail
 * looks like the mail that is already going out.
 */
class BrandedEmailLayout
{
    private const BRAND = '#1a5edb';
    private const PAGE_BG = '#eef2f7';
    private const CARD_BG = '#ffffff';
    private const BORDER = '#d9e2ef';
    private const HEADING = '#1c2a3a';
    private const BODY = '#4a5b6d';
    private const MUTED = '#8a97a6';

    /**
     * @param  string       $heading  the in-card headline
     * @param  string       $body     inner HTML (use p(), calloutBlock(), rows())
     * @param  array|null   $cta      ['label' => string, 'url' => string]
     * @param  string|null  $strapline  the small line under the logo
     */
    public static function wrap(string $heading, string $body, ?array $cta = null, ?string $strapline = null): string
    {
        $strapline = $strapline ?? 'Child healthcare, together';
        $brand = self::BRAND;
        $pageBg = self::PAGE_BG;
        $cardBg = self::CARD_BG;
        $border = self::BORDER;
        $headingColor = self::HEADING;
        $muted = self::MUTED;

        $ctaHtml = $cta === null ? '' : self::button($cta['label'], $cta['url']);

        return <<<HTML
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:{$pageBg};padding:32px 12px;font-family:Arial,Helvetica,sans-serif;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:{$cardBg};border-radius:12px;overflow:hidden;border:1px solid {$border};">
        <tr>
          <td style="background-color:{$brand};padding:28px 32px;text-align:center;">
            <p style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">EmpowerEd</p>
            <p style="margin:8px 0 0;color:#d6e4ff;font-size:14px;">{$strapline}</p>
          </td>
        </tr>
        <tr>
          <td style="padding:32px;">
            <p style="margin:0 0 16px;color:{$headingColor};font-size:18px;font-weight:700;">{$heading}</p>
{$body}
{$ctaHtml}
          </td>
        </tr>
        <tr>
          <td style="background-color:#f7f9fc;padding:16px 32px;text-align:center;border-top:1px solid #e6edf5;">
            <p style="margin:0;color:{$muted};font-size:12px;">&copy; {year} EmpowerEd Child Healthcare. All rights reserved.</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    /** A body paragraph. */
    public static function p(string $html, bool $last = false): string
    {
        $margin = $last ? '0' : '0 0 16px';
        $body = self::BODY;

        return "            <p style=\"margin:{$margin};color:{$body};font-size:15px;line-height:1.6;\">{$html}</p>";
    }

    /** Small print, for the "if this wasn't you" line. */
    public static function note(string $html): string
    {
        $muted = self::MUTED;

        return "            <p style=\"margin:16px 0 0;color:{$muted};font-size:13px;line-height:1.6;\">{$html}</p>";
    }

    /**
     * The bordered monospace block. Used for the generated password, matching
     * the treatment admin_otp gives the one-time code.
     */
    public static function credential(string $label, string $value): string
    {
        $brand = self::BRAND;
        $muted = self::MUTED;

        return <<<HTML
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
              <tr>
                <td style="background-color:#f4f7fb;border:1px dashed {$brand};border-radius:10px;padding:16px 20px;">
                  <p style="margin:0 0 6px;color:{$muted};font-size:12px;text-transform:uppercase;letter-spacing:1px;">{$label}</p>
                  <p style="margin:0;color:{$brand};font-size:20px;font-weight:700;font-family:'Courier New',Courier,monospace;word-break:break-all;">{$value}</p>
                </td>
              </tr>
            </table>
HTML;
    }

    /**
     * A label/value fact list - school name, seats used, import counts.
     *
     * @param  array<string,string>  $pairs
     */
    public static function rows(array $pairs): string
    {
        $border = self::BORDER;
        $muted = self::MUTED;
        $headingColor = self::HEADING;

        $cells = '';
        foreach ($pairs as $label => $value) {
            $cells .= <<<HTML
                <tr>
                  <td style="padding:8px 0;border-bottom:1px solid {$border};color:{$muted};font-size:14px;">{$label}</td>
                  <td style="padding:8px 0;border-bottom:1px solid {$border};color:{$headingColor};font-size:14px;font-weight:700;text-align:right;">{$value}</td>
                </tr>
HTML;
        }

        return <<<HTML
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
{$cells}
            </table>
HTML;
    }

    private static function button(string $label, string $url): string
    {
        $brand = self::BRAND;

        return <<<HTML
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 0;">
              <tr>
                <td style="background-color:{$brand};border-radius:8px;">
                  <a href="{$url}" style="display:inline-block;padding:14px 28px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;">{$label}</a>
                </td>
              </tr>
            </table>
HTML;
    }
}
