<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Ctrlweb\BadgeFactor2\Exceptions\ConfigurationException;
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

    /**
     * Why the last call failed, kept so callers can surface it instead of
     * only writing it to a log nobody may be able to read.
     */
    protected ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    protected function buildRequest($method, $url, array $options = [], array $payload = [])
    {
        $defaultOptions = [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        $mergedOptions = array_merge_recursive($defaultOptions, $options);
        if (!empty($payload)) {
            $mergedOptions = array_merge_recursive($mergedOptions, ['body' => json_encode($payload)]);
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
        $httpClient = new Client([
            'base_uri' => $config->badgr_server_base_url,
            'verify' => false,
            // Without an explicit timeout, Guzzle waits forever (default: 0).
            // A stuck/unreachable Badgr instance would then hang every
            // request indefinitely instead of failing with a catchable
            // exception.
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);

        $this->providerConfiguration['redirectUri'] = $this->authRedirectUri();
        $this->providerConfiguration['urlAuthorize'] = '/o/authorize';
        $this->providerConfiguration['urlAccessToken'] = '/o/token';
        $this->providerConfiguration['urlResourceOwnerDetails'] = '/o/resource';

        $this->addClientInfo();
        $this->addScopes();
        $this->provider = new GenericProvider($this->providerConfiguration, ['httpClient' => $httpClient]);
    }

    /**
     * The redirect URI is only ever used by the interactive authorization
     * code flow (the browser round-trip that first obtains a token). Every
     * server-to-server API call reuses the stored token and never needs it.
     *
     * Resolving it through route('bf2.auth') unconditionally meant that any
     * context where that named route isn't registered - a queued job, a
     * console command, an app booted with cached routes that predate this
     * package - threw RouteNotFoundException here, inside makeProvider().
     * That exception surfaced far from its cause: callers like getResult()
     * caught it and returned an empty array, so badge lists silently came
     * back empty with no indication that anything had gone wrong.
     */
    protected function authRedirectUri(): string
    {
        if (\Illuminate\Support\Facades\Route::has('bf2.auth')) {
            return route('bf2.auth');
        }

        return rtrim((string) config('app.url'), '/') . '/bf2/auth';
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

    /**
     * All the "get" and "confirm" helpers below silently return an empty
     * or false result on any exception (network error, expired/invalid
     * token, unexpected Badgr response...). That makes failures invisible
     * to callers, which in turn makes Sushi-backed models (Badge, Issuer)
     * appear to have zero rows with no trace of why. Log it so failures
     * are at least visible in the logs instead of silently disappearing.
     */
    protected function logBadgrException(string $badgrMethod, string $method, string $endpoint, \Throwable $e): void
    {
        $this->lastError = $this->describeException($e);

        \Log::error('Badgr API call failed: ' . $e->getMessage(), [
            'badgr_method' => $badgrMethod,
            'http_method' => $method,
            'endpoint' => $endpoint,
            'exception' => get_class($e),
        ]);
    }

    /**
     * Turn an exception into something an administrator can act on. Badgr's
     * own validation errors are the useful part of a 4xx body, so they are
     * surfaced as-is; the rest is summarised, since "cURL error 28" tells an
     * admin nothing about what to do next.
     */
    protected function describeException(\Throwable $e): string
    {
        if ($e instanceof ClientException && $e->getResponse()) {
            $body = json_decode((string) $e->getResponse()->getBody(), true);

            $fieldErrors = [];
            foreach ($body['fieldErrors'] ?? [] as $field => $messages) {
                $fieldErrors[] = $field . ': ' . implode(' ', (array) $messages);
            }
            foreach ($body['validationErrors'] ?? [] as $message) {
                $fieldErrors[] = (string) $message;
            }

            if ($fieldErrors) {
                return 'Badgr a refusé la demande (' . implode(' | ', $fieldErrors) . ').';
            }

            return 'Badgr a répondu ' . $e->getResponse()->getStatusCode() . '.';
        }

        if ($e instanceof \GuzzleHttp\Exception\ConnectException) {
            return $this->describeTimeout();
        }

        return $e->getMessage();
    }

    /**
     * Tells apart the two reasons a Badgr call can time out.
     *
     * "No response" covers two very different situations: Badgr being
     * unreachable, or Badgr being reachable but stuck on work it does after
     * creating the badge - notifying an external service, which it does
     * inline and without a timeout of its own. They call for opposite
     * responses, and on environments where the logs are out of reach the
     * message shown on screen is the only thing to go on, so probe a read
     * and say which one it is.
     */
    protected function describeTimeout(): string
    {
        $start = microtime(true);

        try {
            $request = $this->buildRequest('GET', '/v2/issuers_count');
            $this->getProvider()->getHttpClient()->send($request, ['timeout' => 8]);
            $elapsed = round((microtime(true) - $start) * 1000);

            return sprintf(
                'Badgr répond aux lectures (%d ms) mais la création a expiré : le serveur est '
                . 'joignable et reste bloqué sur un traitement qu\'il effectue après avoir créé '
                . 'le badge, typiquement sa notification vers un service externe. Le réseau '
                . 'n\'est pas en cause.',
                $elapsed
            );
        } catch (MissingTokenException | ExpiredTokenException $tokenFailure) {
            // The probe never left the application, so it says nothing about
            // whether Badgr is up - report what it did find instead of
            // blaming the network.
            return 'La création a expiré, et la vérification n\'a pas pu être faite : '
                . 'le jeton d\'accès à Badgr est absent ou expiré. Il faut le renouveler '
                . 'avant de pouvoir conclure sur l\'état du serveur.';
        } catch (\Throwable $probeFailure) {
            return 'Badgr ne répond ni en écriture ni en lecture : le serveur est injoignable '
                . 'ou arrêté. Il s\'agit d\'un problème de réseau ou de service, pas de la '
                . 'création du badge elle-même.';
        }
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

    protected function makeRecoverableRequest(string $method, string $endpoint, array $payload = []): Response
    {
        try {
            $request = $this->buildRequest($method, $endpoint, [], $payload);
            $response = $this->sendRequest($request);

            return $response;
        } catch (MissingTokenException $e) {
            // No need to try refresh on a missing token
            // Try a new auth cycle
            // Let exceptions bubble up since they are not recoverable at this point.
            $this->tryNewAuthCycle();
            $request = $this->buildRequest($method, $endpoint, [], $payload);
            $response = $this->sendRequest($request);

            return $response;
        } catch (ExpiredTokenException $e) {
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
        $response = $this->sendRequest($request);

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
                $response = json_decode($response->getBody(), true);
                if (isset($response['status']['success']) && true === $response['status']['success'] &&
                    isset($response['result'][0]['entityId'])) {
                    return $response['result'][0]['entityId'];
                }
            }
        } catch (Exception $e) {
            $this->logBadgrException(__FUNCTION__, $method, $endpoint, $e);
        }

        return false;
    }

    /**
     * @param PromiseInterface|Response $response
     *
     * @return false|mixed
     */
    protected function getV1Id(string $method, string $endpoint, array $payload = []): string|false
    {
        try {
            $response = $this->makeRecoverableRequest($method, $endpoint, $payload);
            if ($response->getStatusCode() === 201) {
                $response = json_decode($response->getBody(), true);

                return $response['slug'];
            }
        } catch (Exception $e) {
            $this->logBadgrException(__FUNCTION__, $method, $endpoint, $e);
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
                $decoded = json_decode($response->getBody(), true);
                if (
                    isset($decoded['status']['success']) && true === $decoded['status']['success'] &&
                    isset($decoded['result']) && is_array($decoded['result'])
                ) {
                    return $decoded['result'];
                }

                // The request succeeded (HTTP 200) but the payload wasn't in
                // the shape we expect. This previously fell straight through
                // to "return []" with zero trace of why.
                \Log::warning('Badgr API returned 200 with an unexpected response shape', [
                    'badgr_method' => __FUNCTION__,
                    'http_method' => $method,
                    'endpoint' => $endpoint,
                    'body' => mb_substr((string) $response->getBody(), 0, 500),
                ]);
            } else {
                \Log::warning('Badgr API returned a non-200 status', [
                    'badgr_method' => __FUNCTION__,
                    'http_method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $response->getStatusCode(),
                ]);
            }
        } catch (Exception $e) {
            $this->logBadgrException(__FUNCTION__, $method, $endpoint, $e);
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
        } catch (Exception $e) {
            $this->logBadgrException(__FUNCTION__, $method, $endpoint, $e);
        }

        return false;
    }

    /**
     * @param PromiseInterface|Response $response
     *
     * @return false|mixed
     */
    public function getFirstResult(string $method, string $endpoint, array $payload = []): mixed
    {
        try {
            $response = $this->makeRecoverableRequest($method, $endpoint, $payload);
            if ($response->getStatusCode() === 200) {
                $response = json_decode($response->getBody(), true);

                if (isset($response['status']['success']) && true === $response['status']['success'] && isset($response['result'][0])) {
                    return $response['result'][0];
                }
            }
        } catch (Exception $e) {
            $this->logBadgrException(__FUNCTION__, $method, $endpoint, $e);
        }

        return false;
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array  $payload
     *
     * @return mixed
     */
    public function getEmptyResponse(string $method, string $endpoint, array $payload = []): mixed
    {
        try {
            $response = $this->makeRecoverableRequest($method, $endpoint, $payload);
            if ($response->getStatusCode() === 200) {
                return true;
            }
        } catch (Exception $e) {
            $this->logBadgrException(__FUNCTION__, $method, $endpoint, $e);
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
            $this->logBadgrException(__FUNCTION__, $method, $endpoint, $e);
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
            $this->logBadgrException(__FUNCTION__, $method, $endpoint, $e);
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
