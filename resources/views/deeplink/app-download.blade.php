<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1D59F9">
    <title>EmpowerED Health</title>
    <meta name="description" content="EmpowerED is the ultimate health and well-being app for teens aged 11–18, parents, and schools.">
    <link rel="icon" href="{{ $appIcon }}">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: #1D59F9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Lato, Roboto, Helvetica, Arial, sans-serif;
            color: #1f2430;
        }
        .band { height: 140px; background: linear-gradient(180deg, #1238c9 0%, #1D59F9 100%); }
        .card {
            position: relative;
            max-width: 480px;
            min-height: calc(100vh - 100px);
            margin: -60px auto 0;
            padding: 72px 24px 40px;
            background: #fff;
            border-radius: 24px 24px 0 0;
            text-align: center;
        }
        .icon {
            position: absolute;
            top: -56px;
            left: 50%;
            transform: translateX(-50%);
            width: 112px;
            height: 112px;
            padding: 12px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(16, 24, 64, .18);
        }
        .icon img { width: 100%; height: 100%; border-radius: 12px; display: block; }
        .category {
            margin: 8px 0 6px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            color: #1D59F9;
        }
        h1 { margin: 0 0 12px; font-size: 22px; }
        p.desc { margin: 0 auto 28px; max-width: 320px; font-size: 15px; line-height: 1.55; color: #4a5162; }
        .stores { display: flex; flex-direction: column; align-items: center; gap: 12px; }
        .store {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 200px;
            padding: 8px 16px;
            background: #000;
            color: #fff;
            border-radius: 10px;
            text-decoration: none;
            text-align: left;
        }
        .store svg { width: 28px; height: 28px; flex: none; }
        .store small { display: block; font-size: 10px; line-height: 1.1; }
        .store strong { display: block; font-size: 19px; line-height: 1.15; font-weight: 600; }
        .site { display: inline-block; margin-top: 36px; color: #1D59F9; font-weight: 700; text-decoration: none; }
    </style>
</head>
<body>
    <div class="band"></div>
    <main class="card">
        <div class="icon"><img src="{{ $appIcon }}" alt="EmpowerED Health"></div>
        <div class="category">HEALTH</div>
        <h1>EmpowerED Health</h1>
        <p class="desc">EmpowerED is the ultimate health and well-being app for teens aged 11–18, parents, and schools.</p>

        <div class="stores">
            <a class="store" href="{{ $iosStoreUrl }}">
                <svg viewBox="0 0 200 200" aria-hidden="true"><path fill="#fff" d="M136.4 53.5c-15.8 0-22.4 7.5-33.4 7.5-11.2 0-19.8-7.5-33.5-7.5-13.4 0-27.6 8.2-36.6 22.1-12.7 19.6-10.6 56.5 10 87.9 7.4 11.3 17.2 23.9 30.1 24h.2c11.2 0 14.5-7.3 29.9-7.4h.2c15.2 0 18.2 7.4 29.4 7.4h.2c12.9-.1 23.2-14.1 30.6-25.3 5.3-8.1 7.3-12.1 11.3-21.2-29.8-11.3-34.5-53.5-5.1-69.7-9-11.2-21.6-17.8-33.5-17.8zM132.9 12.5c-9.4.6-20.3 6.6-26.7 14.4-5.8 7.1-10.6 17.5-8.7 27.7h.7c10 0 20.2-6 26.2-13.7 5.7-7.3 10.1-17.7 8.5-28.4z"/></svg>
                <span><small>Download on the</small><strong>App Store</strong></span>
            </a>
            <a class="store" href="{{ $androidStoreUrl }}">
                <svg viewBox="0 0 200 200" aria-hidden="true"><path fill="#34A853" d="M56.2 16.7c-11.2-6.3-24.5-1-29.3 9.6l73.6 73.7 37.6-37.6z"/><path fill="#EA4336" d="M138.1 137.6 100.5 100l-73.6 73.7c4.8 10.6 18.1 15.9 29.3 9.6z"/><path fill="#FABD05" d="M172.6 118.3c7.1-4 10.7-11.1 10.7-18.3s-3.6-14.3-10.7-18.3l-34.5-19.3-37.6 37.6 37.6 37.6z"/><path fill="#4285F4" d="M26.9 26.3c-1.2 2.6-1.9 5.6-1.9 8.7v130c0 3.2.7 6.1 1.9 8.7l73.6-73.7z"/></svg>
                <span><small>GET IT ON</small><strong>Google Play</strong></span>
            </a>
        </div>

        <a class="site" href="{{ $webFallbackUrl }}/">{{ preg_replace('#^https?://#', '', $webFallbackUrl) }}</a>
    </main>

    @if ($autoRedirect)
        <script>
            // iPadOS Safari reports a desktop Mac user agent, so the server can't spot it.
            if (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1) {
                window.location.replace({!! json_encode($iosStoreUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!});
            }
        </script>
    @endif
</body>
</html>
