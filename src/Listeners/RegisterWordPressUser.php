<?php

namespace Ctrlweb\BadgeFactor2\Listeners;

use Ctrlweb\BadgeFactor2\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;


class RegisterWordPressUser implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(UserRegistered $event)
    {
/*
        $registerUser = Http::asForm()
            ->acceptJson()
            ->withOptions([
                'verify' => false,
            ])
            ->post(
                config('badgefactor2.wordpress.base_url').'/wp-admin/admin-ajax.php?action=register_user_from_bf2',
                [
                    'username' => $event->user->username,
                    'email' => $event->user->email,
                    'password' => $event->password,
                ]
            );
        Log::debug($registerUser);
*/
    }
}
