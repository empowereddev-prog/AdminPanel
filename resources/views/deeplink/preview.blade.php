<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $item['title'] }} | Empowered Health</title>
    <meta name="description" content="{{ $item['teaser'] }}">
    <meta name="theme-color" content="{{ $item['accent'] ?? '#1a5edb' }}">
    <meta property="og:site_name" content="Empowered Health">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $item['title'] }}">
    <meta property="og:description" content="{{ $item['teaser'] }}">
    <meta property="og:url" content="{{ $item['canonical_url'] }}">
    @if($item['banner'])
        <meta property="og:image" content="{{ $item['banner'] }}">
        <meta name="twitter:image" content="{{ $item['banner'] }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $item['title'] }}">
    <meta name="twitter:description" content="{{ $item['teaser'] }}">
    @if(!empty($iosAppId))
        <meta name="apple-itunes-app" content="app-id={{ $iosAppId }}, app-argument={{ $item['canonical_url'] }}">
    @endif
    <link rel="manifest" href="{{ url('/manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ $appIcon }}">
    <link href="https://fonts.googleapis.com/css?family=Karla:400,700|Roboto:400,500" rel="stylesheet">
    <style>
        :root { --accent: {{ $item['accent'] ?? '#1a5edb' }}; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Karla, Roboto, sans-serif; background: #eef2f8; color: #1b1b1b; }
        .page { max-width: 680px; margin: 0 auto; min-height: 100vh; background: #fff; }
        .hero { position: relative; background: linear-gradient(160deg, var(--accent), #243447); min-height: 180px; }
        .hero img { width: 100%; max-height: 360px; object-fit: cover; display: block; }
        .brand { padding: 16px 24px 0; font-size: 13px; letter-spacing: .04em; text-transform: uppercase; color: #5b6570; }
        .body { padding: 8px 24px 32px; }
        h1 { font-size: 28px; line-height: 1.25; margin: 8px 0 12px; }
        .meta { color: #5b6570; font-size: 14px; margin: 0 0 16px; }
        .teaser { font-size: 17px; line-height: 1.55; color: #2c333a; }
        .cta-row { margin-top: 24px; display: flex; flex-wrap: wrap; gap: 10px; }
        .cta { display: inline-block; padding: 12px 18px; border-radius: 8px; text-decoration: none; font-weight: 700; }
        .cta.primary { background: var(--accent); color: #fff; }
        .cta.secondary { background: #243447; color: #fff; }
        .note { margin-top: 20px; font-size: 13px; color: #5b6570; }
        .footer { padding: 16px 24px 28px; font-size: 12px; color: #8a94a0; }
        .smart-banner {
            display: none;
            position: sticky;
            top: 0;
            z-index: 20;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: #f7f7f7;
            border-bottom: 1px solid #d8d8d8;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .smart-banner.is-visible { display: flex; }
        .smart-banner .sb-close {
            background: none; border: 0; color: #8e8e93; font-size: 22px; line-height: 1; padding: 0 4px; cursor: pointer;
        }
        .smart-banner .sb-icon { width: 40px; height: 40px; border-radius: 9px; object-fit: cover; }
        .smart-banner .sb-copy { flex: 1; min-width: 0; }
        .smart-banner .sb-copy strong { display: block; font-size: 13px; }
        .smart-banner .sb-copy span { display: block; font-size: 11px; color: #8e8e93; }
        .smart-banner .sb-get {
            background: #007aff; color: #fff; text-decoration: none; font-size: 13px; font-weight: 700;
            padding: 6px 14px; border-radius: 16px; letter-spacing: .02em;
        }
        .smart-banner.is-android { background: #fff; border-bottom-color: #e0e0e0; }
        .smart-banner.is-android .sb-get { background: #01875f; border-radius: 4px; text-transform: uppercase; font-size: 12px; }
    </style>
</head>
<body>
    <div class="smart-banner" id="smart-banner" hidden>
        <button type="button" class="sb-close" id="smart-banner-close" aria-label="Close">&times;</button>
        <img class="sb-icon" src="{{ $appIcon }}" alt="">
        <div class="sb-copy">
            <strong>Empowered Health</strong>
            <span id="smart-banner-sub">Get the app — free</span>
        </div>
        <a class="sb-get" id="smart-banner-get" href="{{ $iosStore }}">VIEW</a>
    </div>
    <article class="page">
        <div class="hero">
            @if($item['banner'])
                <img src="{{ $item['banner'] }}" alt="">
            @endif
        </div>
        <p class="brand">Empowered Health · {{ ucfirst($item['type'] ?? 'article') }}</p>
        <div class="body">
            <h1>{{ $item['title'] }}</h1>
            <p class="meta">
                @if(!empty($item['category'])){{ $item['category'] }} · @endif
                @if(!empty($item['audience_label'])){{ $item['audience_label'] }}@endif
                @if(!empty($item['author'])) · {{ $item['author'] }}@endif
            </p>
            @if($item['teaser'])
                <p class="teaser">{{ $item['teaser'] }}</p>
            @endif
            <div class="cta-row">
                <a class="cta primary" id="open-app" href="{{ $schemeUrl }}">Open in app</a>
                <a class="cta secondary" href="{{ $iosStore }}">App Store</a>
                <a class="cta secondary" href="{{ $androidStore }}">Google Play</a>
            </div>
            <p class="note">If the app is not installed, use App Store or Google Play, then open this link again. Sign in with the matching account (parent, staff, or child).</p>
        </div>
        <div class="footer">Preview only — the full article opens in the Empowered Health app.</div>
    </article>
    <script>
        (function () {
            var open = document.getElementById('open-app');
            var banner = document.getElementById('smart-banner');
            var getBtn = document.getElementById('smart-banner-get');
            var sub = document.getElementById('smart-banner-sub');
            var closeBtn = document.getElementById('smart-banner-close');
            var scheme = @json($schemeUrl);
            var intent = @json($androidIntent);
            var iosStore = @json($iosStore);
            var androidStore = @json($androidStore);
            var iosAppId = @json($iosAppId ?? '');
            var ua = navigator.userAgent || '';
            var isAndroid = /Android/i.test(ua);
            var isIOS = /iPhone|iPad|iPod/i.test(ua);
            var isIOSSafari = isIOS && /Safari/i.test(ua) && !/CriOS|FxiOS|EdgiOS|OPiOS|YaBrowser/i.test(ua);
            var dismissed = false;
            try { dismissed = sessionStorage.getItem('eh-smart-banner') === '1'; } catch (e) {}

            if (open && isAndroid) {
                open.setAttribute('href', intent);
            }
            if (open) {
                open.addEventListener('click', function (e) {
                    if (!isIOS) return;
                    e.preventDefault();
                    var t = Date.now();
                    window.location.href = scheme;
                    setTimeout(function () {
                        if (Date.now() - t < 1800) {
                            window.location.href = iosStore;
                        }
                    }, 1400);
                });
            }

            var showCustom = !dismissed && (isAndroid || (isIOS && !(isIOSSafari && iosAppId)));
            if (banner && showCustom) {
                banner.removeAttribute('hidden');
                banner.classList.add('is-visible');
                if (isAndroid) {
                    banner.classList.add('is-android');
                    if (sub) sub.textContent = 'Free · Google Play';
                    if (getBtn) {
                        getBtn.textContent = 'INSTALL';
                        getBtn.setAttribute('href', intent);
                    }
                } else {
                    if (sub) sub.textContent = 'Free · App Store';
                    if (getBtn) {
                        getBtn.textContent = 'VIEW';
                        getBtn.setAttribute('href', iosStore);
                    }
                }
            }
            if (closeBtn && banner) {
                closeBtn.addEventListener('click', function () {
                    banner.classList.remove('is-visible');
                    banner.setAttribute('hidden', '');
                    try { sessionStorage.setItem('eh-smart-banner', '1'); } catch (e) {}
                });
            }
        })();
    </script>
</body>
</html>
