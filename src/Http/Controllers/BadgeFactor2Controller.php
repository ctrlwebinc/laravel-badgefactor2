<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers;

use App\Nova\BadgrConfig as NovaBadgrConfig;
use Ctrlweb\BadgeFactor2\Models\BadgrConfig;
use Ctrlweb\BadgeFactor2\Services\Badgr\BadgrService;
use Ctrlweb\BadgeFactor2\Services\Badgr\BadgrAdminProvider;
use Illuminate\Http\Request;

class BadgeFactor2Controller extends Controller
{
    private $oauthStateParameterName = 'bf2_oauth_state';
    private $authHomeUrlParameterName = 'bf2_auth_home_url';

    public function getAccessTokenFromAuthCode(Request $request,  BadgrAdminProvider $adminProvider)
    {
        if ( null === $request->input('code') || null === $request->input('state') || $request->input('state') !== $request->session()->get($this->oauthStateParameterName))
        {
            abort(400);
        }
        $adminProvider->attemptAccessTokenRetrievalFromCode($request->input('code'));

        if (null !== $request->session()->get($this->authHomeUrlParameterName))
        {
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
}
