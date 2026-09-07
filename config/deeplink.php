<?php

return [

    'public_base_url' => rtrim(env('DEEPLINK_PUBLIC_BASE_URL', env('APP_URL', 'https://admin.empoweredhealth.asia')), '/'),

    'apple_team_id' => env('DEEPLINK_APPLE_TEAM_ID', 'R8V8Y45CQZ'),

    'android_package' => env('DEEPLINK_ANDROID_PACKAGE', 'asia.empoweredhealth'),

    'android_sha256_fingerprints' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DEEPLINK_ANDROID_SHA256', ''))
    ))),

    'ios_store_url' => env('DEEPLINK_IOS_STORE_URL', 'https://apps.apple.com'),

    'android_store_url' => env('DEEPLINK_ANDROID_STORE_URL', 'https://play.google.com/store/apps/details?id=asia.empoweredhealth'),

    'scheme' => env('DEEPLINK_SCHEME', 'empowered'),

    'types' => ['article', 'podcast'],

];
