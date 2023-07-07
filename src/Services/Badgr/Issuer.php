<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Carbon\CarbonInterface;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;

class Issuer extends BadgrProvider
{

    /**
     * @return array|bool
     * @throws Exception
     */
    public function all(): array|bool
    {
        if (Cache::has('issuers')) {
            return json_decode(Cache::get('issuers'));
        }

        $response = $this->getClient()
            ->get('/v2/issuers');

        $response = $this->getResult($response);

        if ($response) {
            Cache::put('issuers', json_encode($response), 60);
        }

        return $response;
    }

    /**
     * @return int|bool
     * @throws Exception
     */
    public function count(): int|bool
    {
        if (Cache::has('issuers_count')) {
            return Cache::get('issuers_count');
        }

        $response = $this->getClient()
            ->get('/v2/issuers_count');

        $response = $this->getCount($response);

        if ($response) {
            Cache::put('issuers_count', $response, 60);
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
     * @return mixed
     * @throws Exception
     */
    public function getBySlug(string $entityId): mixed
    {
        if (Cache::has('issuer_'.$entityId)) {
            return json_decode(Cache::get('issuer_'.$entityId));
        }

        $response = $this->getClient()
            ->get('/v2/issuers/'.$entityId);

        $response = $this->getFirstResult($response);

        if ($response) {
            Cache::put('issuer_'.$entityId, json_encode($response), 60);
        }

        return $response;
    }


    /**
     * @param string $name
     * @param string $email
     * @param string $url
     * @param string|null $description
     * @param string|null $image
     * @return mixed
     * @throws Exception
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

        $response = $this->getClient()->post('/v2/issuers', $payload);

        Cache::forget('issuers');

        return $this->getEntityId($response);
    }

    /**
     * @param string $entityId
     * @param string $name
     * @param string $email
     * @param string $url
     * @param string|null $description
     * @param string|null $image
     * @return bool
     * @throws Exception
     */
    public function update(
        string  $entityId, string $name, string $email, string $url,
        ?string $description = null, ?string $image = null): bool
    {
        $payload = [
            'name' => $name,
            'email' => $email,
            'url' => $url,
        ];

        if (null !== $description) {
            $payload['description'] = $description;
        }

        if (null !== $image && $this->prepareImage($image)) {
            $payload['image'] = $this->prepareImage($image);
        }

        $response = $this->getClient()->put('/v2/issuers/' . $entityId, $payload);

        Cache::forget('issuers');
        Cache::forget('issuer_'.$entityId);

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
        $response = $this->getClient()->delete('/v2/issuers/'.$entityId);

        Cache::forget('issuers');
        Cache::forget('issuer_'.$entityId);

        if (null !== $response && ($response->status() === 204 || $response->status() === 404)) {
            return true;
        }

        return false;
    }
}

