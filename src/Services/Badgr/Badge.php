<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Carbon\CarbonInterface;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Badge extends BadgrProvider
{

    /**
     * @return array|bool
     * @throws Exception
     */
    public function all(): array|bool
    {
        if (Cache::has('badges')) {
            return json_decode(Cache::get('badges'));
        }

        $client = $this->getClient();

        if ( ! $client ) return false;

        $response = $this->getClient()->get('/v2/badgeclasses');

        $response = $this->getResult($response);

        if ($response) {
            Cache::put('badges', json_encode($response), 60);
        }

        return $response;
    }

    /**
     * @return int|bool
     * @throws Exception
     */
    public function count(): int|bool
    {
        if (Cache::has('badges_count')) {
            return Cache::get('badges_count');
        }

        $response = $this->getClient()->get('/v2/badgeclasses_count');

        $response = $this->getCount($response);

        if ($response) {
            Cache::put('badges_count', $response, 60);
        }

        return $response;
    }

    /**
     * @param string $name
     * @return array|bool
     * @throws Exception
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
     * @return mixed
     * @throws Exception
     */
    public function getBySlug(string $entityId): mixed
    {
        if (Cache::has('badge_'.$entityId)) {
            return json_decode(Cache::get('badge_'.$entityId));
        }

        $response = $this->getClient()->get('/v2/badgeclasses/' . $entityId);

        $response = $this->getFirstResult($response);

        if ($response) {
            Cache::put('badge_'.$entityId, json_encode($response), 60);
        }

        return $response;
    }

    public function getByIssuer(string $entityId): mixed
    {
        if (Cache::has('badges_by_issuer_'.$entityId)) {
            return Cache::get('badges_by_issuer_'.$entityId);
        }

        $response = $this->getClient()
            ->get('/v2/issuers/' . $entityId . '/badgeclasses');

        $response = $this->getResult($response);

        if ($response) {
            Cache::put('badges_by_issuer_'.$issuer, $response, 60);
        }

        return $response;
    }


    /**
     * @param string $image
     * @param string $name
     * @param string $issuer
     * @param string|null $description
     * @param string|null $criteriaNarrative
     * @return mixed
     */
    public function add(string $image, string $name, string $issuer, ?string $description, ?string $criteriaNarrative): mixed
    {
        $issuer = json_decode($issuer)->entityId;
        $payload = [
            'image'             => $this->prepareImage($image),
            'name'              => $name,
            'issuer'            => $issuer,
        ];

        if (null !== $description) {
            $payload['description'] = $description;
        }

        if (null !== $criteriaNarrative) {
            $payload['criteriaNarrative'] = $criteriaNarrative;
        }

        $response = $this->getClient()->post('/v2/badgeclasses', $payload);

        Cache::forget('badges');

        return $this->getEntityId($response);
    }

    /**
     * @param string $entityId
     * @param string $name
     * @param string $issuer
     * @param string|null $description
     * @param string|null $criteriaNarrative
     * @param string|null $image
     * @return bool
     * @throws Exception
     */
    public function update(
        string  $entityId, string $name, string $issuer,
        ?string $description, ?string $criteriaNarrative, ?string $image ): bool
    {

        $issuer = json_decode($issuer)->entityId;
        $payload = [
            'name'              => $name,
            'issuer'            => $issuer,
        ];

        if (null !== $description) {
            $payload['description'] = $description;
        }

        if (null !== $criteriaNarrative) {
            $payload['criteriaNarrative'] = $criteriaNarrative;
        }

        if (null !== $image && $this->prepareImage($image)) {
            $payload['image'] = $this->prepareImage($image);
        }

        $response = $this->getClient()->put('/v2/badgeclasses/' . $entityId, $payload);

        Cache::forget('badges');
        Cache::forget('badge_'.$entityId);

        if (null !== $response && $response->status() === 200) {
            return true;

        }

        return false;
    }

    /**
     * @param string $entityId
     * @return bool
     * @throws Exception
     */
    public function delete(string $entityId): bool
    {
        $response = $this->getClient()->delete('/v2/badgeclasses/' . $entityId);

        Cache::forget('badges');
        Cache::forget('badge_'.$entityId);

        if (null !== $response && ($response->status() === 204 || $response->status() === 404)) {
            return true;
        }

        return false;
    }
}

