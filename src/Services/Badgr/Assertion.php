<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Exception;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;


class Assertion extends BadgrProvider
{
    /**
     * @param string $entityId
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function getBySlug(string $entityId): mixed
    {
        if (Cache::has('assertion_'.$entityId)) {
            return json_decode(Cache::get('assertion_'.$entityId));
        }

        $client = $this->getClient();
        if (!$client) {
            return false;
        }

        $response = $client->get('/v2/assertions/'.$entityId);

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
        if (!$client) {
            return false;
        }

        $response = $client->get('/v2/issuers/'.$entityId.'/assertions');

        $response = $this->getResult($response);

        if ($response) {
            Cache::put('assertions_by_issuer_'.$entityId, $response, 60);
        }

        return $response;
    }

    public function getByBadgeClass(string $entityId): mixed
    {
        if (Cache::has('assertions_by_badgeclass_'.$entityId)) {
            return Cache::get('assertions_by_badgeclass_'.$entityId);
        }

        $client = $this->getClient();
        if (!$client) {
            return false;
        }

        $response = $client->get('/v2/badgeclasses/'.$entityId.'/assertions');

        $response = $this->getResult($response);

        if ($response) {
            Cache::put('assertions_by_badgeclass_'.$entityId, $response, 60);
        }

        return $response;
    }

    public function add(string $issuer, string $badge, string $recipient, string $recipientType='email', ?Carbon $issuedOn=null, ?string $evidenceUrl=null, ?string $evidenceNarrative=null)
    {
        $client = $this->getClient();
        if (!$client) {
            return false;
        }

        $issuerId = json_decode($issuer)->entityId;
        $badgeId = json_decode($badge)->entityId;
        $recipientMail = json_decode($recipient)->email;

        $payload = [
            'recipient' => [
                'identity' => $recipientMail,
                'type' => $recipientType
            ],
        ];

        if (null !== $issuedOn )
        {
			$payload['issuedOn'] = $issuedOn->format( 'c' );
		}

        if (null !== $evidenceNarrative || null !== $evidenceUrl )
        {
			$evidence = [];
			if (null !== $evidenceNarrative )
            {
				$evidence['narrative'] = $evidenceNarrative;
			}
			if ( null !== $evidenceUrl)
            {
				$evidence['url'] = $evidenceUrl;
			}
			$payload['evidence'] = [$evidence];
		}

        $response = $client->post('/v2/badgeclasses/'.$badgeId.'/assertions', $payload);

        $entityId = $this->getEntityId($response);

        if ($entityId ) {
            Cache::put('assertion_'.$entityId, json_encode($response), 60);
            Cache::forget('assertions_by_badgeclass_'.$badgeId);
            Cache::forget('assertions_by_issuer_'.$issuerId);
        }

        return $entityId;
    }
}
