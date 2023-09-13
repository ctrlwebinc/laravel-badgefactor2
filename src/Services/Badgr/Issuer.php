<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Exception;
use Illuminate\Support\Facades\Cache;

class Issuer extends BadgrAdminProvider
{
    /**
     * @throws Exception
     *
     * @return array|bool
     */
    public function all(): array|bool
    {
        if (Cache::has('issuers')) {
            return json_decode(Cache::get('issuers'));
        }

        $client = $this->getClient();
        if (!$client) {
            return false;
        }

        $response = $client->get('/v2/issuers');

        $response = $this->getResult($response);

        if ($response) {
            Cache::put('issuers', json_encode($response), 86400);
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
        if (Cache::has('issuers_count')) {
            return Cache::get('issuers_count');
        }

        $client = $this->getClient();
        if (!$client) {
            return false;
        }

        $response = $client->get('/v2/issuers_count');

        $response = $this->getCount($response);

        if ($response) {
            Cache::put('issuers_count', $response, 86400);
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
        $issuers = $this->all();
        if ($issuers) {
            $issuers = collect($issuers);

            return $issuers->filter(function ($issuer) use ($name) {
                if (strtolower($issuer['name']) === strtolower($name)) {
                    return $issuer;
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
        if (Cache::has('issuer_'.$entityId)) {
            return json_decode(Cache::get('issuer_'.$entityId));
        }

        $response = $this->getFirstResult('GET','/v2/issuers/'.$entityId);

        if ($result) {
            Cache::put('issuer_'.$entityId, json_encode($response), 86400);
        }

        return $response;
    }

    /**
     * @param string      $name
     * @param string      $email
     * @param string      $url
     * @param string|null $description
     * @param string|null $image
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function add(string $name, string $email, string $url, ?string $description, ?string $image = null): mixed
    {
        $payload = [
            'name'        => $name,
            'email'       => $email,
            'url'         => $url,
        ];

        if (null !== $description) {
            $payload['description'] = $description;
        }

        if (null !== $image) {
            $payload['image'] = $this->prepareImage($image);
        }

        $response = $client->post('/v2/issuers', $payload);

        Cache::forget('issuers');

        return $this->getEntityId('POST', '/v2/issuers', $payload);
    }

    /**
     * @param string      $entityId
     * @param string      $name
     * @param string      $email
     * @param string      $url
     * @param string|null $description
     * @param string|null $image
     *
     * @throws Exception
     *
     * @return bool
     */
    public function update(
        string $entityId,
        string $name,
        string $email,
        string $url,
        ?string $description = null,
        ?string $image = null
    ): bool {
        $client = $this->getClient();
        if (!$client) {
            return false;
        }

        $payload = [
            'name'  => $name,
            'email' => $email,
            'url'   => $url,
        ];

        if (null !== $description) {
            $payload['description'] = $description;
        }

        if (null !== $image && $this->prepareImage($image)) {
            $payload['image'] = $this->prepareImage($image);
        }

        $response = $client->put('/v2/issuers/'.$entityId, $payload);

        Cache::forget('issuers');
        Cache::forget('issuer_'.$entityId);

        if (null !== $response && $response->status() === 200) {
            return true;
        }

        return false;
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
        $client = $this->getClient();
        if (!$client) {
            return false;
        }

        $response = $client->delete('/v2/issuers/'.$entityId);

        Cache::forget('issuers');
        Cache::forget('issuer_'.$entityId);

        if (null !== $response && ($response->status() === 204 || $response->status() === 404)) {
            return true;
        }

        return false;
    }
}
