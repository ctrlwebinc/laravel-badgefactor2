<?php

namespace Ctrlweb\BadgeFactor2\Listeners;

use Ctrlweb\BadgeFactor2\Events\SessionTokenDestroyed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;

class RemoveTokenFromLMS implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param \App\Events\SessionTokenDestroyed $event
     *
     * @return void
     */
    public function handle(SessionTokenDestroyed $event)
    {
        $registerToken = Http::asForm()
            ->acceptJson()
            ->withOptions([
                'verify' => false,
            ])
            ->post(
                config('badgefactor2.wordpress.base_url').'/wp-admin/admin-ajax.php?action=remove_laravel_session_token',
                [
                    'email' => $event->user->email,
                    'token' => $event->token,
                ]
            );
    }
}
