<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers;

use Ctrlweb\BadgeFactor2\Services\Badgr\Assertion;
use Ctrlweb\BadgeFactor2\Services\Badgr\Badge;
use Ctrlweb\BadgeFactor2\Services\Badgr\BadgrAdminProvider;
use Ctrlweb\BadgeFactor2\Services\Badgr\BadgrProvider;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager as Image;
use Intervention\Image\Drivers\Imagick\Driver;

class BadgeFactor2Controller extends Controller
{
    private $oauthStateParameterName = 'bf2_oauth_state';
    private $authHomeUrlParameterName = 'bf2_auth_home_url';

    public function getAccessTokenFromAuthCode(Request $request, BadgrAdminProvider $adminProvider)
    {
        if (null === $request->input('code') || null === $request->input('state') || $request->input('state') !== $request->session()->get($this->oauthStateParameterName)) {
            abort(400);
        }
        $adminProvider->attemptAccessTokenRetrievalFromCode($request->input('code'));

        if (null !== $request->session()->get($this->authHomeUrlParameterName)) {
            return redirect($request->session()->get($this->authHomeUrlParameterName));
        }

        return redirect('/');
    }

    public function initiateAuthCodeRetrieval(Request $request, BadgrAdminProvider $provider)
    {
        $authorizationUrl = $provider->getAuthorizationUrl();
        $request->session()->put($this->oauthStateParameterName, $provider->getState());
        $request->session()->put($this->authHomeUrlParameterName, $request->headers->get('referer'));

        return redirect($authorizationUrl);
    }

    public function getBadgrAssertion(Request $request, string $entityId)
    {
        $assertion = app(Assertion::class)->getBySlug($entityId);
        if (is_array($assertion)) {
            $assertion = (object) $assertion;

        }
        $manager = new Image(new Driver());
        $file = file_get_contents($assertion->image);
        $image = $manager->read($file);

        return response($image->encode(), 200)->header('Content-Type', $image->encode()->mediaType());
    }

    public function getBadgrBadge(Request $request, string $entityId)
    {
        $badge = app(Badge::class)->getBySlug($entityId);
        if (is_array($badge)) {
            $badge = (object) $badge;
        }
        $manager = new Image(new Driver());
        $file = file_get_contents($badge->image);
        $image = $manager->read($file);

        return response($image->encode(), 200)->header('Content-Type', $image->encode()->mediaType());
    }
}
