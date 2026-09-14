<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Empowered Health</title>
</head>
<body>
    <script>
        (function () {
            var web = {!! json_encode($webFallbackUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!};
            var scheme = {!! json_encode($schemeUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!};
            var intent = {!! json_encode($androidIntent, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!};
            var ua = navigator.userAgent || '';
            var isAndroid = /Android/i.test(ua);
            var isIPadOs = navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
            var isIOS = /iPhone|iPad|iPod/i.test(ua) || isIPadOs;

            function goWeb() {
                window.location.replace(web);
            }

            if (isAndroid) {
                window.location.replace(intent);
                setTimeout(goWeb, 1500);
                return;
            }

            if (isIOS) {
                var started = Date.now();
                var hidden = false;
                function onHide() {
                    hidden = document.hidden || document.webkitHidden;
                }
                document.addEventListener('visibilitychange', onHide);
                window.location.href = scheme;
                setTimeout(function () {
                    document.removeEventListener('visibilitychange', onHide);
                    if (!hidden && document.visibilityState !== 'hidden' && Date.now() - started < 2500) {
                        goWeb();
                    }
                }, 1200);
                return;
            }

            goWeb();
        })();
    </script>
</body>
</html>
