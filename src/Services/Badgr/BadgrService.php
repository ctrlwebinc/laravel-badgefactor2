<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use App\Models\BadgeRequest;
use App\Models\BadgrConfig;
use Ctrlweb\BadgeFactor2\Services\Badgr\BadgrClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class BadgrService
{

    private $badgrClient;

    public function __construct()
    {
        $badgrConfig = BadgrConfig::first();
        if ($badgrConfig) {
            $this->badgrClient = new BadgrClient(
                $badgrConfig->client_id,
                $badgrConfig->client_secret,
                $badgrConfig->redirect_uri,
                config('badgefactor2.badgr.server_url'),
                config('badgefactor2.badgr.admin_scopes')
            );
        }
    }

    public function getBadgrClient()
    {
        return $this->badgrClient;
    }

    public function getAllBadgesByIssuer($issuer)
    {
        /*
        if (Cache::has('badges-by-issuer-'.$issuer)) {
            return Cache::get('badges-by-issuer-'.$issuer);
        }

        $response = $this->getBadgrClient()
            ->getHttpClient(BadgrConfig::first()->getAccessTokenToArray())
            ->get('/v2/issuers/' . $issuer . '/badgeclasses');
        $response = $response->json('result');
        Cache::put('badges-by-issuer-'.$issuer, $response, 60);
        return collect($response);
        */
    }

    public function approveBadgeRequest(BadgeRequest $badgeRequest)
    {
        /*
        $approver = Auth::user();
        $response = $this->getBadgrClient()
            ->getHttpClient(BadgrConfig::first()->getAccessTokenToArray())
            ->baseUrl(config('cadre21.wordpress_base_url'))
            ->withBasicAuth($approver->email, $approver->wp_application_password)
            ->post('/wp-json/bf2-laravel/v1/approve-badge-request');
        return $response->json();
        */
    }

}
