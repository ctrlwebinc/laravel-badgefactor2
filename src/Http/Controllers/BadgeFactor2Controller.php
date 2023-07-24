<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers;

use App\Nova\BadgrConfig as NovaBadgrConfig;
use Ctrlweb\BadgeFactor2\Models\BadgrConfig;
use Ctrlweb\BadgeFactor2\Services\Badgr\BadgrService;
use Illuminate\Http\Request;

class BadgeFactor2Controller extends Controller
{
    public function getAccessTokenFromAuthCode(Request $request, BadgrService $badgeService)
    {
        try {
            $accessToken = $badgeService->getBadgrClient()->getAccessTokenUsingAuthCode($request->code);

            $badgrConfig = BadgrConfig::first();

            if ($badgrConfig) {
                $badgrConfig->update([
                    'access_token'  => $accessToken->getToken(),
                    'refresh_token' => $accessToken->getRefreshToken(),
                    'expires_at'    => now()->setTimestamp($accessToken->getExpires()),
                ]);

                return redirect()->route('nova.pages.detail', [
                    'resource'   => NovaBadgrConfig::uriKey(),
                    'resourceId' => $badgrConfig->id,
                ]);
            }
        } catch (\Exception $e) {
            throw new \Exception('Connection exception: '.$e->getMessage());
        }
    }
}
