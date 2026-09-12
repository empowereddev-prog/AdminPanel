<?php

return [

    /*
     * Deliberately does NOT fall back to APP_URL.
     *
     * APP_URL is legitimately localhost or a bare server IP in most
     * environments, and this value is baked into every stored canonical_url and
     * into the Universal Link / App Link association. Falling back to it is how
     * http://<ec2-ip>/d/article/154 ended up persisted in production: those
     * links can never open the app, because iOS and Android only honour https
     * on a domain verified via .well-known.
     */
    'public_base_url' => rtrim(env('DEEPLINK_PUBLIC_BASE_URL', 'https://admin.empoweredhealth.asia'), '/'),

    'apple_team_id' => env('DEEPLINK_APPLE_TEAM_ID', 'R8V8Y45CQZ'),

    'android_package' => env('DEEPLINK_ANDROID_PACKAGE', 'asia.empoweredhealth'),

    'android_sha256_fingerprints' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DEEPLINK_ANDROID_SHA256', ''))
    ))),

    'ios_store_url' => env('DEEPLINK_IOS_STORE_URL', 'https://apps.apple.com/app/empowered-health/id6742772237'),

    'ios_app_id' => env('DEEPLINK_IOS_APP_ID', '6742772237'),

    'app_icon' => env('DEEPLINK_APP_ICON', '/assets/images/new.png'),

    'android_store_url' => env('DEEPLINK_ANDROID_STORE_URL', 'https://play.google.com/store/apps/details?id=asia.empoweredhealth'),

    'scheme' => env('DEEPLINK_SCHEME', 'empowered'),

    'types' => ['article', 'podcast'],

];
