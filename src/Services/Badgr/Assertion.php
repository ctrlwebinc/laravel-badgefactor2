<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Carbon\CarbonInterface;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Assertion extends BadgrProvider
{
    /**
     * @param string $entityId
     * @return mixed
     * @throws Exception
     */
    public function getBySlug(string $entityId): mixed
    {
        if (Cache::has('assertion_'.$entityId)) {
            return json_decode(Cache::get('assertion_'.$entityId));
        }

        $client = $this->getClient();
        if ( ! $client ) return [];

        $response = $client->get('/v2/assertions/' . $entityId);

        $response = $this->getFirstResult($response);

        if ($response) {
            Cache::put('assertion_'.$entityId, json_encode($response), 60);
        }

        return $response;
    }

    public function getByIssuer(string $entityId): mixed
    {
        if (Cache::has('assertions_by_issuer_'.$entityId)) {
            return Cache::get('assertions_by_issuer_'.$entityId);
        }

        $client = $this->getClient();
        if ( ! $client ) return [];

        $response = $client->get('/v2/issuers/' . $entityId . '/assertions');

        $response = $this->getResult($response);

        if ($response) {
            Cache::put('assertions_by_issuer_'.$entityId, $response, 60);
            return $response;
        }

        return [];

    }

    public function getByBadgeClass(string $entityId): mixed
    {
        if (Cache::has('assertions_by_badgeclass_'.$entityId)) {
            return Cache::get('assertions_by_badgeclass_'.$entityId);
        }

        $client = $this->getClient();
        if ( ! $client ) return [];

        $response = $client->get('/v2/badgeclasses/' . $entityId . '/assertions');

        $response = $this->getResult($response);

        if ($response) {
            Cache::put('assertions_by_badgeclass_'.$entityId, $response, 60);
            return $response;
        }

        return [];
    }

}

