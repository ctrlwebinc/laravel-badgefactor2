<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Ctrlweb\BadgeFactor2\Models\BadgrConfig;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Ctrlweb\BadgeFactor2\Models\User;
use Ctrlweb\BadgeFactor2\Exceptions\MissingTokenException;
use Ctrlweb\BadgeFactor2\Exceptions\ExpiredTokenException;


class BadgrRecipientProvider extends BadgrProvider
{
    protected $recipient;

    function __construct(User $recipient)
    {
        $this->recipient = $recipient;
    }

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

    protected function getToken() : ?AccessTokenInterface
    {
        return $this->recipient->getTokenSet();
    }

    protected function tryNewAuthCycle()
    {
        // Try to get token from password grant
        // Throw exception if fails
        // Save token if succeeds
        $newToken = $this->getProvider()->getAccessToken('password',[
            'username' => $this->recipient->email,
            'password' => $this->recipient->badgr_encrypted_password,
        ]);
        $this->saveToken($newToken);
    }

    protected function saveToken(AccessTokenInterface $token)
    {
        $this->recipient->saveTokenSet($token);
    }
}
