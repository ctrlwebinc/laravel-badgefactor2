<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use BadgeFactor2\Exceptions\ConfigurationException;
use Ctrlweb\BadgeFactor2\Exceptions\ExpiredTokenException;
use Ctrlweb\BadgeFactor2\Exceptions\MissingTokenException;
use Ctrlweb\BadgeFactor2\Models\BadgrConfig;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Storage;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessTokenInterface;

abstract class BadgrProvider
{
    protected $provider;
    protected $config;
    protected $providerConfiguration = [];

    protected function buildRequest($method, $url, array $options = [], array $payload = [])
    {
        $defaultOptions = [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        $mergedOptions = array_merge_recursive($defaultOptions, $options);
        if (!empty($payload)) {
            $mergedOptions = array_merge_recursive($mergedOptions, ['body' => $payload]);
        }

        return $this->getProvider()->getAuthenticatedRequest($method, $url, $this->getVerifiedToken(), $mergedOptions);
    }

    protected function getToken(): ?AccessTokenInterface
    {
        return $this->getConfig()->getTokenSet();
    }

    protected function getVerifiedToken(): AccessTokenInterface
    {
        $token = $this->getToken();
        $this->checkToken($token);

        return $token;
    }

    protected function sendRequest(Request $request): Response
    {
        return $this->getProvider()->getHttpClient()->send($request);
    }

    protected function getConfig(): BadgrConfig
    {
        if (null === $this->config) {
            $this->config = BadgrConfig::first();
        }
        if (null === $this->config) {
            throw new ConfigurationException('No Badgr Config.');
        }

        return $this->config;
    }

    protected function makeProvider(): void
    {
        $config = $this->getConfig();
        $httpClient = new Client(['base_uri' => $config->badgr_server_base_url]);

        $this->providerConfiguration['redirectUri'] = route('bf2.auth');
        $this->providerConfiguration['urlAuthorize'] = '/o/authorize';
        $this->providerConfiguration['urlAccessToken'] = '/o/token';
        $this->providerConfiguration['urlResourceOwnerDetails'] = '/o/resource';

        $this->addClientInfo();
        $this->addScopes();
        $this->provider = new GenericProvider($this->providerConfiguration, ['httpClient' => $httpClient]);
    }

    protected function getProvider(): GenericProvider
    {
        if (null === $this->provider) {
            $this->makeProvider();
        }

        return $this->provider;
    }

    protected function addClientInfo()
    {
        $config = $this->getConfig();
        $this->providerConfiguration['clientId'] = $config->client_id;
        $this->providerConfiguration['clientSecret'] = $config->client_secret;
    }

    protected function addScopes()
    {
        $config = $this->getConfig();
        $this->providerConfiguration['scopes'] = 'rw:profile rw:backpack rw:issuer rw:serverAdmin';
    }

    protected function checkToken($token): void
    {
        if (null === $token) {
            throw new MissingTokenException('No token retreived from token repository');
        }
        if ($token->hasExpired()) {
            throw new ExpiredTokenException('Token has expired.');
        }
    }

    protected function makeRecoverableRequest(string $method, string $endpoint, array $payload = []) : Response
    {
        try {
            $request = $this->buildRequest($method, $endpoint, [], $payload);
            $response = $this->getProvider()->getHttpClient()->send($request);

            return $response;
        } catch (MissingTokenException $e) {
            // No need to try refresh on a missing token
            // Try a new auth cycle
            // Let exceptions bubble up since they are not recoverable at this point.
            $this->tryNewAuthCycle();
            $request = $this->buildRequest($method, $endpoint, [], $payload);
            $response = $this->getProvider()->getHttpClient()->send($request);

            return $response;
        } catch( ExpiredTokenException $e) {
            // Let processing continue for these exceptions since rest of precessing is to try refresh
        } catch (ClientException $e) {
            // Check for 401 exception, rethrow anything else
            if ($e->getCode() != 401) {
                throw $e;
            }
        }

        // Try a refresh, let all exceptions bubble up
        $this->refreshToken();
        $request = $this->buildRequest($method, $endpoint, [], $payload);
        $response = $this->getProvider()->getHttpClient()->send($request);

        return $response;
    }

    protected function tryNewAuthCycle()
    {
        throw new Exception('Code auth cycle cannot be initiated in background.');
    }

    protected function refreshToken()
    {
        $newAccessToken = $this->getProvider()->getAccessToken('refresh_token', [
            'refresh_token' => $this->getToken()->getRefreshToken(),
        ]);

        $this->saveToken($newAccessToken);
    }

    protected function saveToken(AccessTokenInterface $token)
    {
        $this->getConfig()->saveTokenSet($token);
    }

    /**
     * @param PromiseInterface|Response $response
     *
     * @return false|mixed
     */
    protected function getEntityId(string $method, string $endpoint, array $payload = []): string|false
    {
        try {
            $response = $this->makeRecoverableRequest($method, $endpoint, $payload);
            if ($response->getStatusCode() === 201) {
                $response = json_decode($response->getBody(),true);
                if (isset($response['status']['success']) && true === $response['status']['success'] &&
                    isset($response['result'][0]['entityId'])) {
                    return $response['result'][0]['entityId'];
                }
            }
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * @param PromiseInterface|Response $response
     *
     * @return array|false
     */
    public function getResult(string $method, string $endpoint, array $payload = []): array|false
    {
        try {
            $response = $this->makeRecoverableRequest($method, $endpoint, $payload);
            if ($response->getStatusCode() === 200) {
                $response = json_decode($response->getBody(), true);
                if (
                    isset($response['status']['success']) && true === $response['status']['success'] &&
                    isset($response['result']) && is_array($response['result'])
                ) {
                    return $response['result'];
                }
            }
        }
        catch (Exception $e) {
        }

        return [];
    }

    /**
     * @param PromiseInterface|Response $response
     *
     * @return false|int
     */
    public function getCount(string $method, string $endpoint, array $payload = []): int|false
    {
        try {
            $response = $this->makeRecoverableRequest($method, $endpoint, $payload);
            if ($response->getStatusCode() === 200) {
                $response = json_decode($response->getBody(), true);
                if (
                    isset($response['count']) && is_numeric($response['count'])
                ) {
                    return intval($response['count']);
                }
            }
        }
        catch (Exception $e) {
        }

        return false;
    }

    /**
     * @param PromiseInterface|Response $response
     *
     * @return false|mixed
     */
    protected function getFirstResult(string $method, string $endpoint, array $payload = []): mixed
    {
        try {
            $response = $this->makeRecoverableRequest($method, $endpoint, $payload);
            if ($response->getStatusCode() === 200) {
                $response = json_decode($response->getBody(),true);

                if (isset($response['status']['success']) && true === $response['status']['success'] && isset($response['result'][0])) {
                    return $response['result'][0];
                }
            }
        } catch (Exception $e) {
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
        return $this->getCount('PUT', '/v2/badgeclasses_count/issuer/'.$issuerId);
    }

    protected function confirmDeletion(string $method, string $endpoint, array $payload = []): bool
    {
        try {
            $response = $this->makeRecoverableRequest($method, $endpoint, $payload);
            if (null !== $response && ($response->getStatusCode() === 204 || $response->getStatusCode() === 404)) {
                return true;
            }
        } catch (Exception $e) {
        }

        return false;
    }

    protected function confirmUpdate(string $method, string $endpoint, array $payload = []): bool
    {
        try {
            $response = $this->makeRecoverableRequest($method, $endpoint, $payload);
            if (null !== $response && $response->getStatusCode() === 200) {
                return true;
            }
        } catch (Exception $e) {
        }

        return false;
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
        return $this->makeRecoverableRequest('DELETE', '/v2/badgeclasses/'.$badgeClassId);
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
