<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;

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

    public function add(string $issuer, string $badge, string $recipient, string $recipientType = 'email', ?Carbon $issuedOn = null, ?string $evidenceUrl = null, ?string $evidenceNarrative = null): mixed
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
                'type'     => $recipientType,
            ],
        ];

        if (null !== $issuedOn) {
            $payload['issuedOn'] = $issuedOn->format('c');
        }

        if (null !== $evidenceNarrative || null !== $evidenceUrl) {
            $evidence = [];
            if (null !== $evidenceNarrative) {
                $evidence['narrative'] = $evidenceNarrative;
            }
            if (null !== $evidenceUrl) {
                $evidence['url'] = $evidenceUrl;
            }
            $payload['evidence'] = [$evidence];
        }

        $response = $client->post('/v2/badgeclasses/'.$badgeId.'/assertions', $payload);

        $entityId = $this->getEntityId($response);

        if ($entityId) {
            Cache::put('assertion_'.$entityId, json_encode($response), 60);
            Cache::forget('assertions_by_badgeclass_'.$badgeId);
            Cache::forget('assertions_by_issuer_'.$issuerId);
        }

        return $entityId;
    }

    public function update(string $entityId, array $parameters = []): bool
    {
        $client = $this->getClient();
        if (!$client) {
            return false;
        }

        // Setup payload.
        $payload = [];

        if (isset($parameters['issuedOn']) && 0 !== strlen($parameters['issuedOn'])) {
            $payload['issuedOn'] = $parameters['issuedOn']->format('c');
        }

        $evidence = [];
        if (isset($parameters['evidenceNarrative']) && (null !== $parameters['evidenceNarrative']) && (0 !== strlen($parameters['evidenceNarrative']))) {
            $evidence['narrative'] = $parameters['evidenceNarrative'];
        }
        if (isset($parameters['evidenceUrl']) && (null !== $parameters['evidenceUrl']) && (0 !== strlen($parameters['evidenceUrl']))) {
            $evidence['url'] = $parameters['evidenceUrl'];
        }
        if (!empty($evidence)) {
            $payload['evidence'] = [$evidence];
        }

        if (isset($parameters['recipient'])) {
            $payload['recipient'] = [
                'identity' => json_decode($parameters['recipient'])->email,
                'type'     => 'email'
            ];
        }

		if (empty($payload)) {
            // Nothing to change, update not possible.
            return false;
        }

        $response = $client->put('/v2/assertions/'.$entityId, $payload);

        $result = $this->getFirstResult($response);

        if ($result) {
            Cache::put('assertion_'.$entityId, json_encode($result), 60);
            Cache::forget('assertions_by_badgeclass_'.$result['badgeclass']);
            Cache::forget('assertions_by_issuer_'.$result['issuer']);
        }

        return true;
    }

    public function revoke(string $entityId, string $reason=null): bool
    {
        $client = $this->getClient();
        if (!$client) {
            return false;
        }

        // Get assertion first to determine issuer and badgeclass for the purpose of invalidating the cache
        $response = $client->get('/v2/assertions/'.$entityId);

        $result = $this->getFirstResult($response);

        if (!$result || $result['revoked'] == true) {
            // No cache operation required.
            return true;
        }

        $issuerId = $result['issuer'];
        $badgeId = $result['badgeclass'];

        $response = $client->delete('/v2/assertions/'.$entityId, [
            'revocation_reason' => $reason ?? 'No reason specified'
        ]);

        if (null !== $response && ($response->status() === 204 || $response->status() === 200 || $response->status() === 404 || $response->status() === 400)) {
            Cache::forget('assertion_'.$entityId);
            Cache::forget('assertions_by_badgeclass_'.$badgeId);
            Cache::forget('assertions_by_issuer_'.$issuerId);

            return true;
        }

        return false;
    }
}
