<?php

use App\Http\Controllers\DeepLinkController;
use Illuminate\Support\Facades\Route;

Route::get('.well-known/apple-app-site-association', [DeepLinkController::class, 'appleAppSiteAssociation']);
Route::get('apple-app-site-association', [DeepLinkController::class, 'appleAppSiteAssociation']);
Route::get('.well-known/assetlinks.json', [DeepLinkController::class, 'assetLinks']);
Route::get('manifest.webmanifest', [DeepLinkController::class, 'webManifest']);

Route::get('d/{type}/{id}', [DeepLinkController::class, 'fallback'])
    ->where(['type' => 'article|podcast', 'id' => '[0-9]+'])
    ->name('deeplink.fallback');
