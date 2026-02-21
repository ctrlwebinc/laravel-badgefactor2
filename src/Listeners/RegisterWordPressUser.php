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
        $url = config('badgefactor2.wordpress.base_url') . '/wp-json/bf2/v1/register';

        try {
            $payload = [
                'username'      => $event->user->username,
                'email'         => $event->user->email,
                'password'      => $event->password,
                'first_name'    => $event->user->first_name,
                'last_name'     => $event->user->last_name,
                'user_nicename' => $event->user->first_name.' '.$event->user->last_name,
                'slug'          => $event->user->slug,
            ];

            // Body JSON brut (signé)
            $body = json_encode($payload, JSON_UNESCAPED_SLASHES);

            $ts    = (string) time();
            $nonce = (string) Str::uuid();

            $appKey = config('app.key');
            if (!is_string($appKey) || $appKey === '') {
                throw new \RuntimeException('config(app.key) is empty.');
            }

            if (str_starts_with($appKey, 'base64:')) {
                $secret = base64_decode(substr($appKey, 7), true);
                if ($secret === false) {
                    throw new \RuntimeException('APP_KEY is not valid base64.');
                }
            } else {
                $secret = $appKey;
            }

            $baseString = $ts . "\n" . $nonce . "\n" . $body;
            $sig = base64_encode(hash_hmac('sha256', $baseString, $secret, true));

            $response = Http::acceptJson()
                ->withoutRedirecting()
                ->withOptions([
                    'verify' => false,
                ])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-BF2-TS'     => $ts,
                    'X-BF2-NONCE'  => $nonce,
                    'X-BF2-SIG'    => $sig,
                ])
                ->withBody($body, 'application/json')
                ->post($url);

            } catch (\Exception $e) {
                return response()->json([
                    'error'   => true,
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ], 500);
            }
                
    }
}
