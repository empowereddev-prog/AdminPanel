<?php

namespace App\Listeners;

use App\Models\UserLogin;
use Illuminate\Auth\Events\Login;
 
class LogUserLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        // $user = $event->user;

        // $user->last_login_at = now();
        // $user->save();

        // UserLogin::create([
        //     'user_id' => $user->id,
        //     'logged_in_at' => now(),
        // ]);
    }
}
