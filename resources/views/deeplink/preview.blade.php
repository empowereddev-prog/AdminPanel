<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $item['title'] }} | Empowered Health</title>
    <meta name="description" content="{{ $item['teaser'] }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $item['title'] }}">
    <meta property="og:description" content="{{ $item['teaser'] }}">
    <meta property="og:url" content="{{ $item['canonical_url'] }}">
    @if($item['banner'])
        <meta property="og:image" content="{{ $item['banner'] }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <style>
        body { font-family: Karla, Roboto, sans-serif; margin: 0; background: #f4f6fb; color: #1b1b1b; }
        .wrap { max-width: 640px; margin: 40px auto; padding: 24px; background: #fff; border-radius: 12px; }
        img.banner { width: 100%; border-radius: 8px; margin-bottom: 16px; }
        .muted { color: #5b6570; font-size: 14px; }
        .cta { display: inline-block; margin: 8px 8px 0 0; padding: 10px 16px; background: #1a5edb; color: #fff; text-decoration: none; border-radius: 6px; }
        .cta.secondary { background: #243447; }
        .note { margin-top: 16px; font-size: 13px; color: #5b6570; }
    </style>
</head>
<body>
    <div class="wrap">
        @if($item['banner'])
            <img class="banner" src="{{ $item['banner'] }}" alt="">
        @endif
        <p class="muted">{{ ucfirst($item['type']) }} · Opens in the Empowered Health app</p>
        <h1>{{ $item['title'] }}</h1>
        @if($item['teaser'])
            <p>{{ $item['teaser'] }}</p>
        @endif
        <p>
            <a class="cta" href="{{ $item['canonical_url'] }}">Open in app</a>
            <a class="cta secondary" href="{{ $iosStore }}">App Store</a>
            <a class="cta secondary" href="{{ $androidStore }}">Google Play</a>
        </p>
        <p class="note">Install the app, then tap Open in app. Links work after the content is published. Sign in with the matching account type if prompted.</p>
    </div>
</body>
</html>
