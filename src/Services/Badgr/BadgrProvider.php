<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Carbon\CarbonInterface;
use Ctrlweb\BadgeFactor2\Models\BadgrConfig;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;

class BadgrProvider
{
    private BadgrClient $client;

    public function __construct()
    {
        $badgrConfig = BadgrConfig::first();
        if ($badgrConfig) {
            $this->client = new BadgrClient(
                $badgrConfig->client_id,
                $badgrConfig->client_secret,
                $badgrConfig->redirect_uri,
                config('badgefactor2.badgr.server_url'),
                config('badgefactor2.badgr.admin_scopes')
            );
        }
    }

    /**
     * @throws Exception
     */
    public function getClient()
    {
        if (isset($this->client)) {
            return $this->client->getHttpClient(
                BadgrConfig::first()->getAccessTokenToArray()
            );
        }

        return null;
    }

    /**
     * @param PromiseInterface|Response $response
     *
     * @return false|mixed
     */
    protected function getEntityId(PromiseInterface|Response $response): mixed
    {
        if ($response->status() === 201) {
            $response = $response->json();
            if (isset($response['status']['success']) && true === $response['status']['success'] &&
                isset($response['result'][0]['entityId'])) {
                return $response['result'][0]['entityId'];
            }
        }

        return false;
    }

    /**
     * @param PromiseInterface|Response $response
     *
     * @return array|false
     */
    public function getResult(PromiseInterface|Response $response): array|false
    {
        if ($response->status() === 200) {
            $response = $response->json();
            if (
                isset($response['status']['success']) && true === $response['status']['success'] &&
                isset($response['result']) && is_array($response['result'])
            ) {
                return $response['result'];
            }
        }

        return false;
    }

    /**
     * @param PromiseInterface|Response $response
     *
     * @return false|int
     */
    public function getCount(PromiseInterface|Response $response): int|false
    {
        if ($response->status() === 200) {
            $response = $response->json();
            if (
                isset($response['count']) && is_numeric($response['count'])
            ) {
                return intval($response['count']);
            }
        }

        return false;
    }

    /**
     * @param PromiseInterface|Response $response
     *
     * @return false|mixed
     */
    protected function getFirstResult(PromiseInterface|Response $response): mixed
    {
        if ($response->status() === 200) {
            $response = $response->json();

            if (isset($response['status']['success']) && true === $response['status']['success'] && isset($response['result'][0])) {
                return $response['result'][0];
            }
        }

        return false;
    }

    /**
     * @param string $issuerId
     *
     * @throws Exception
     *
     * @return false|int
     */
    public function getAllBadgeClassesByIssuerSlugCount(string $issuerId): bool|int
    {
        $response = $this->getClient()->put('/v2/badgeclasses_count/issuer/'.$issuerId);

        return $this->getCount($response);
    }

    /**
     * @param string $badgeClassId
     *
     * @throws Exception
     *
     * @return bool
     */
    public function deleteBadgeClass(string $badgeClassId): bool
    {
        $response = $this->getClient()->delete('/v2/badgeclasses/'.$badgeClassId);

        if (null !== $response && ($response->status() === 204 || $response->status() === 404)) {
            return true;
        }

        return false;
    }

    /**
     * @param string      $badgeClassId
     * @param string      $recipientIdentifier
     * @param string      $recipientType
     * @param mixed|null  $issuedOn
     * @param string|null $evidenceUrl
     * @param string|null $evidenceNarrative
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function addAssertion(
        string $badgeClassId,
        string $recipientIdentifier,
        string $recipientType = 'email',
        mixed $issuedOn = null,
        ?string $evidenceUrl = null,
        ?string $evidenceNarrative = null
    ): mixed {
        $payload = [
            'recipient' => [
                'identity' => $recipientIdentifier,
                'type'     => $recipientType,
            ],
        ];

        if ($issuedOn instanceof CarbonInterface) {
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

            $payload['evidence'] = $evidence;
        }

        $response = $this->getClient()->post('/v2/badgeclasses/'.$badgeClassId.'/assertions', $payload);

        return $this->getEntityId($response);
    }

    /**
     * Prepares image to be sent to Badgr API.
     *
     * @param string $image
     *
     * @return string
     */
    public function prepareImage(string $image)
    {
        if (Storage::disk(config('nova.storage_disk'))->exists($image)) {
            $mimeType = Storage::disk(config('nova.storage_disk'))->mimeType($image);
            $rawFile = Storage::disk(config('nova.storage_disk'))->get($image);

            if ('image/svg' === $mimeType) {
                $mimeType .= '+xml';
            } elseif ('image/jpeg' === $mimeType || 'image/gif' === $mimeType) {
                ob_start();
                $gdImage = imagecreatefromstring($rawFile);
                $success = imagepng($gdImage);
                $rawFile = ob_get_contents();
                $mimeType = 'image/png';
                ob_end_clean();
            }

            $file = base64_encode($rawFile);

            return "data:{$mimeType};base64,{$file}";
        }

        return null;
    }
}
