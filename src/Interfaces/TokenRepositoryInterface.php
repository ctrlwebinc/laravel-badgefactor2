<?php

namespace Ctrlweb\BadgeFactor2\Interfaces;

use League\OAuth2\Client\Token\AccessTokenInterface;

interface TokenRepositoryInterface
{
    public function getTokenSet() : ?AccessTokenInterface;

    public function saveTokenSet(AccessTokenInterface $tokenSet);
}
