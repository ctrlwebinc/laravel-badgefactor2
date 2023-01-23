<?php

namespace Ctrlweb\BadgeFactor2\Badgr;

class BadgrProvider
{

    private BadgrClient $client;

    public function __construct(BadgrClient $client)
    {
        $this->client = $client;
    }

    public function getAllBadges() {
        $this->client->getHttpClient();
    }
}
