<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $stripeSecret = env('STRIPE_TEST_SK');
        if (!empty($stripeSecret)) {
            \Stripe\Stripe::setApiKey($stripeSecret);
        }
    }
}
