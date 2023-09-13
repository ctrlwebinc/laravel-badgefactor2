<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Ctrlweb\BadgeFactor2\Models\BadgrConfig;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;
use League\OAuth2\Client\Token\AccessTokenInterface;


class BadgrRecipientProvider extends BadgrProvider
{
    protected function addClientInfo()
    {
        $config = $this->getConfig();
        $this->providerConfiguration['clientId'] = $config->password_client_id;
        $this->providerConfiguration['clientSecret'] = $config->password_client_secret;
    }

    protected function addScopes()
    {
        $config = $this->getConfig();
        $this->providerConfiguration['scopes'] = 'rw:profile rw:backpack';
    }

    protected function getToken() : AccessTokenInterface
    {
        throw new Exception('Not implemented.');
    }
}
