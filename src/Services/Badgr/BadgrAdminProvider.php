<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

class BadgrAdminProvider extends BadgrProvider
{
    public function getAuthorizationUrl() : string
    {
        return $this->getConfig()->badgr_server_base_url . $this->getProvider()->getAuthorizationUrl();
    }

    public function getState() : string
    {
        return $this->getProvider()->getState();
    }

    public function attemptAccessTokenRetrievalFromCode(string $code) : void
    {
        $newToken = $this->getProvider()->getAccessToken('authorization_code', [
            'code' => $code
        ]);
        $this->saveToken($newToken);
    }
}
