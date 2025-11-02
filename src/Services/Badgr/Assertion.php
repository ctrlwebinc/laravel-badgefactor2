<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;

class Assertion extends BadgrAdminProvider
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

        $response = $this->getFirstResult('GET', '/v2/assertions/'.$entityId);

        if ($response && is_array($response)) {
            Cache::put('assertion_'.$entityId, json_encode($response), config('badgefactor2.cache_duration'));
        }

        return $response;
    }

    public function getByIssuer(string $entityId): mixed
    {
        if (Cache::has('assertions_by_issuer_'.$entityId)) {
            return Cache::get('assertions_by_issuer_'.$entityId);
        }

        $response = $this->getResult('GET', '/v2/issuers/'.$entityId.'/assertions');

        if ($response) {
            Cache::put('assertions_by_issuer_'.$entityId, $response, config('badgefactor2.cache_duration'));
        }

        return $response;
    }

    public function getByBadgeClass(string $entityId): mixed
    {
        if (Cache::has('assertions_by_badgeclass_'.$entityId)) {
            return Cache::get('assertions_by_badgeclass_'.$entityId);
        }

        $response = $this->getResult('GET', '/v2/badgeclasses/'.$entityId.'/assertions');

        if ($response) {
            Cache::put('assertions_by_badgeclass_'.$entityId, $response, config('badgefactor2.cache_duration'));
        }

        return $response;
    }

    public function add(string $issuer, string $badge, string $recipient, string $recipientType = 'email', ?Carbon $issuedOn = null, ?string $evidenceUrl = null, ?string $evidenceNarrative = null, ?Carbon $expires = null): mixed
    {

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

        if (null !== $expires) {
            $payload['expires'] = $expires->format('c');
        }

        
        $entityId = $this->getEntityId('POST', '/v2/badgeclasses/'.$badgeId.'/assertions', $payload);

        if ($entityId) {
            Cache::forget('assertions_by_badgeclass_'.$badgeId);
            Cache::forget('assertions_by_issuer_'.$issuerId);
        }
        dd( $entityId );
        return $entityId;
    }

    public function update(string $entityId, array $parameters = []): bool
    {
        // Setup payload.
        $payload = [];

        if (isset($parameters['issuedOn']) && 0 !== strlen($parameters['issuedOn'])) {
            $payload['issuedOn'] = $parameters['issuedOn']->format('c');
        }
       
        if (isset($parameters['expires']) && 0 !== strlen($parameters['expires'])) {
            $payload['expires'] = $parameters['expires']->format('c');
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
                'type'     => 'email',
            ];
        }

        if (empty($payload)) {
            // Nothing to change, update not possible.
            return false;
        }

        $result = $this->getFirstResult('PUT', '/v2/assertions/'.$entityId, $payload);
        
        if ($result) {
            Cache::put('assertion_'.$entityId, json_encode($result), config('badgefactor2.cache_duration'));
            Cache::forget('assertions_by_badgeclass_'.$result['badgeclass']);
            Cache::forget('assertions_by_issuer_'.$result['issuer']);
        }

        return true;
    }

    public function revoke(string $entityId, string $reason = null): bool
    {
        // Get assertion first to determine issuer and badgeclass for the purpose of invalidating the cache
        $result = $this->getFirstResult('GET', '/v2/assertions/'.$entityId);

        if (!$result || $result['revoked'] == true) {
            // No cache operation required.
            return true;
        }

        $issuerId = $result['issuer'];
        $badgeId = $result['badgeclass'];

        return $this->confirmDeletion('DELETE', '/v2/assertions/'.$entityId, [
            'revocation_reason' => $reason ?? 'No reason specified',
        ]);
    }
}
