<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers;

use Ctrlweb\BadgeFactor2\Services\Badgr\Assertion;
use Ctrlweb\BadgeFactor2\Services\Badgr\BadgrAdminProvider;
use Ctrlweb\BadgeFactor2\Services\Badgr\BadgrProvider;
use Illuminate\Http\Request;

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
        $imageContent = file_get_contents($assertion['image']);
        return response()->stream(function () use ($imageContent) {
            echo $imageContent;
        });
    }
}
