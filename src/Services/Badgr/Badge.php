<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Exception;
use Illuminate\Support\Facades\Cache;

class Badge extends BadgrAdminProvider
{
    /**
     * @throws Exception
     *
     * @return array|bool
     */
    public function all(): array|bool
    {
        if (Cache::has('badges')) {
            return json_decode(Cache::get('badges'));
        }

        $response = $this->getResult('GET', '/v2/badgeclasses');

        if ($response) {
            Cache::put('badges', json_encode($response), config('badgefactor2.cache_duration'));
        }

        return $response;
    }

    /**
     * @throws Exception
     *
     * @return int|bool
     */
    public function count(): int|bool
    {
        if (Cache::has('badges_count')) {
            return Cache::get('badges_count');
        }

        $response = $this->getCount('GET', '/v2/badgeclasses_count');

        if ($response) {
            Cache::put('badges_count', $response, config('badgefactor2.cache_duration'));
        }

        return $response;
    }

    /**
     * @param string $name
     *
     * @throws Exception
     *
     * @return array|bool
     */
    public function getByName(string $name): array|bool
    {
        $badges = $this->all();
        if ($badges) {
            $badges = collect($badges);

            return $badges->filter(function ($badge) use ($name) {
                if (strtolower($badge['name']) === strtolower($name)) {
                    return $badge;
                }

                return null;
            })->filter()->first();
        }

        return false;
    }

    /**
     * @param string $entityId
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function getBySlug(string $entityId): mixed
    {
        if (Cache::has('badge_'.$entityId)) {
            return json_decode(Cache::get('badge_'.$entityId));
        }

        $response = $this->getFirstResult('GET', '/v2/badgeclasses/'.$entityId);

        if ($response) {
            Cache::put('badge_'.$response['entityId'], json_encode($response), config('badgefactor2.cache_duration'));
        }

        return $response;
    }

    public function getByIssuer(string $entityId): mixed
    {
        if (Cache::has('badges_by_issuer_'.$entityId)) {
            return Cache::get('badges_by_issuer_'.$entityId);
        }

        $response = $this->getResult('GET', '/v2/issuers/'.$entityId.'/badgeclasses');

        if ($response) {
            Cache::put('badges_by_issuer_'.$entityId, $response, config('badgefactor2.cache_duration'));
        }

        return $response;
    }

    /**
     * @param string      $image
     * @param string      $name
     * @param string      $issuer
     * @param string|null $description
     * @param string|null $criteriaNarrative
     *
     * @return mixed
     */
    public function add(string $image, string $name, string $issuer, ?string $description, ?string $criteriaNarrative, ?array $expires): mixed
    {
        $issuer = json_decode($issuer)->entityId;
        $payload = [
            'image'             => $this->prepareImage($image),
            'name'              => $name,
            'issuer'            => $issuer,
            'description'       => $description,
            'criteriaNarrative' => $criteriaNarrative,
        ];

        if ( null !== $expires && is_array( $expires ) ) {
            $payload['expires'] = $expires;
        }

        Cache::forget('badges');

        return $this->getEntityId('POST', '/v2/badgeclasses', $payload);
    }

    /**
     * @param string      $entityId
     * @param string      $name
     * @param string      $issuer
     * @param string|null $description
     * @param string|null $criteriaNarrative
     * @param string|null $image
     *
     * @throws Exception
     *
     * @return bool
     */
    public function update(
        string $entityId,
        string $name,
        string $issuer,
        ?string $description,
        ?string $criteriaNarrative,
        ?string $image,
        ?array $expires
    ): bool {
        $issuer = json_decode($issuer)->entityId;
        $payload = [
            'name'              => $name,
            'issuer'            => $issuer,
            'description'       => $description,
            'criteriaNarrative' => $criteriaNarrative,
        ];

        if (null !== $image && $this->prepareImage($image)) {
            $payload['image'] = $this->prepareImage($image);
        }

        if ( null !== $expires && is_array( $expires ) ) {
            $payload['expires'] = $expires;
        }

        Cache::forget('badges');
        Cache::forget('badge_'.$entityId);

        return $this->confirmUpdate('PUT', '/v2/badgeclasses/'.$entityId, $payload);
    }

    /**
     * @param string $entityId
     *
     * @throws Exception
     *
     * @return bool
     */
    public function delete(string $entityId): bool
    {
        Cache::forget('badges');
        Cache::forget('badge_'.$entityId);

        return $this->confirmDeletion('DELETE', '/v2/badgeclasses/'.$entityId);
    }
}
