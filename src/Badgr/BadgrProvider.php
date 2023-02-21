<?php

namespace Ctrlweb\BadgeFactor2\Badgr;

use Carbon\CarbonInterface;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;

class BadgrProvider
{

    private BadgrClient $client;

    public function __construct(BadgrClient $client)
    {
        $this->client = $client;
    }

    /**
     * @throws Exception
     */
    public function getClient()
    {
        return $this->client->getHttpClient();
    }

    /**
     * @param PromiseInterface|Response $response
     * @return false|mixed
     */
    private function getEntityId(PromiseInterface|Response $response): mixed
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
     * @return false|int
     */
    public function getCount(PromiseInterface|Response $response): int|false
    {
        if ($response->status() === 200) {
            $response = $response->json();
            if (
                isset($response['status']['success']) && true === $response['status']['success'] &&
                isset($response['count']) && is_numeric($response['count'])
            ) {
                return intval($response['count']);
            }
        }

        return false;
    }

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $password
     * @return mixed
     * @throws Exception
     */
    public function addUser(string $firstName, string $lastName, string $email, string $password): mixed
    {
        $payload = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'url' => '',
            'telephone' => '',
            'slug' => '',
            'agreed_terms_version' => 1,
            'marketing_opt_in' => false,
            'has_password_set' => false,
            'source' => 'bf2',
            'password' => $password,
        ];

        $response = $this->getClient()->post('/v1/user/profile', $payload);

        if (null !== $response && $response->status() === 201) {
            return $response->json('slug');
        }

        return false;
    }

    /**
     * @param string $entityId
     * @param string $oldPassword
     * @param string $newPassword
     * @return bool
     * @throws Exception
     */
    public function changeUserPassword(string $entityId, string $oldPassword, string $newPassword): bool
    {
        $payload = [
            'password' => $newPassword,
            'currentPassword' => $oldPassword,
        ];

        $response = $this->getClient()->post('/v2/users/' . $entityId, $payload);

        if (null !== $response && $response->status() === 200) {
            $response = $response->json();

            if (isset($response['status']['success']) && true === $response['status']['success']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $entityId
     * @return mixed
     * @throws Exception
     */
    public function getUser(string $entityId): mixed
    {
        $response = $this->getClient()->get('/v2/users/' . $entityId);
        return $this->getFirstResult($response);
    }

    /**
     * @param string $entityId
     * @return bool
     * @throws Exception
     */
    public function checkUserVerified(string $entityId): bool
    {

        $response = $this->getClient()->get('/v2/users/' . $entityId);

        if (null !== $response && $response->status() === 200) {
            $response = $response->json();

            if (
                isset($response['status']['success']) && true === $response['status']['success'] &&
                isset($response['result'][0]) && isset($response['result'][0]->recipient)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $entityId
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @return bool
     * @throws Exception
     */
    public function updateUser(string $entityId, string $firstName, string $lastName, string $email): bool
    {

        $payload = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'emails' => [
                [
                    'email' => $email,
                    'primary' => true
                ]
            ]
        ];

        $response = $this->getClient()->put('/v2/users/' . $entityId, $payload);

        if (null !== $response && $response->status() === 200) {
            return true;
        }

        return false;
    }

    /**
     * @return array|bool
     * @throws Exception
     */
    public function getAllIssuers(): array|bool
    {
        $response = $this->getClient()->get('/v2/issuers');

        return $this->getResult($response);
    }

    /**
     * @return int|bool
     * @throws Exception
     */
    public function getAllIssuersCount(): int|bool
    {
        $response = $this->getClient()->get('/v2/issuers_count');

        if (null !== $response && $response->status() === 200) {
            $response = $response->json();
            if (
                isset($response['status']['success']) && true === $response['status']['success'] &&
                isset($response['count']) && is_numeric($response['count'])
            ) {
                return $response['count'];
            }
        }

        return false;
    }

    /**
     * @param $name
     * @return array|bool
     * @throws Exception
     */
    public function getIssuerByName($name): array|bool
    {
        $issuers = $this->getAllIssuers();
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
    public function getIssuerBySlug(string $entityId): mixed
    {

        $response = $this->getClient()->get('/v2/issuers/' . $entityId);

        return $this->getFirstResult($response);
    }

    /**
     * @param PromiseInterface|Response $response
     * @return false|mixed
     */
    private function getFirstResult(PromiseInterface|Response $response): mixed
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
     * @param string $entityId
     * @return bool
     * @throws Exception
     */
    public function deleteIssuer(string $entityId): bool
    {

        $response = $this->getClient()->delete('/v2/issuers/' . $entityId);

        if (null !== $response && ($response->status() === 204 || $response->status() === 404)) {
            return true;
        }

        return false;
    }


    /**
     * @param string $name
     * @param string $email
     * @param string $url
     * @param string $description
     * @param string|null $image
     * @return mixed
     * @throws Exception
     */
    public function addIssuer(string $name, string $email, string $url, string $description, ?string $image = null): mixed
    {
        $payload = [
            'name' => $name,
            'image' => $image,
            'email' => $email,
            'url' => $url,
            'description' => $description,
        ];

        $response = $this->getClient()->post('/v2/issuers', $payload);

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
    public function updateIssuer(
        string  $entityId, string $name, string $email, string $url,
        ?string $description = null, ?string $image = null): bool
    {
        $payload = [
            'name' => $name,
            'email' => $email,
            'url' => $url,
        ];

        if (null !== $image) {
            $payload['image'] = $image;
        }

        if (null !== $description) {
            $payload['description'] = $description;
        }

        $response = $this->getClient()->put('/v2/issuers/' . $entityId, $payload);

        if (null !== $response && $response->status() === 200) {
            return true;
        }

        return false;
    }

    /**
     * @param string $badgeClassName
     * @param string $issuerId
     * @param string $description
     * @param string|null $image
     * @return false|mixed
     * @throws Exception
     */
    public function addBadgeClass(string $badgeClassName, string $issuerId, string $description, string $image = null): mixed
    {
        $payload = [
            'name' => $badgeClassName,
            'issuer' => $issuerId,
            'description' => $description,
        ];

        if (null !== $image) {
            $payload['image'] = $image;
        }

        $response = $this->getClient()->post('/v2/badgeclasses', $payload);

        return $this->getEntityId($response);
    }

    /**
     * @param string $issuerId
     * @return bool|array
     * @throws Exception
     */
    public function getAllBadgeClassesByIssuerSlug(string $issuerId): bool|array
    {
        $response = $this->getClient()->get('/v2/issuers/' . $issuerId . '/badgeclasses');

        return $this->getResult($response);
    }

    /**
     * @param string $issuerId
     * @return false|int
     * @throws Exception
     */
    public function getAllBadgeClassesByIssuerSlugCount(string $issuerId): bool|int
    {
        $response = $this->getClient()->put('/v2/badgeclasses_count/issuer/' . $issuerId);

        return $this->getCount($response);
    }

    public function getAllBadgeClasses(): bool
    {
        $response = $this->getClient()->get('/v2/badgeclasses');

        return $this->getResult($response);
    }

    public function getAllBadgeClassesCount()
    {
        $response = $this->getClient()->get('/v2/badgeclasses_count');

        return $this->getCount($response);
    }

    public function getBadgeClassByBadgeClassSlug(string $badgeClassId)
    {

        $response = $this->getClient()->get('/v2/badgeclasses/' . $badgeClassId);

        if (null !== $response && $response->status() === 200) {
            $response = $response->json();
            if (
                isset($response['status']['success']) && true === $response['status']['success'] &&
                isset($response['result']) && isset($response['result'][0])
            ) {
                return $response['result'][0];
            }
        }

        return false;
    }

    public function updateBadgeClass(string $badgeClassId, string $name, ?string $description = null, ?string $image = null): bool
    {
        $payload = [
            'name' => $name,
            'description' => $description,
        ];

        if (null !== $image) {
            $payload['image'] = $image;
        }

        $response = $this->getClient()->put('/v2/badgeclasses/' . $badgeClassId, $payload);

        if (null !== $response && $response->status() === 200) {
            return true;
        }

        return false;
    }

    public function deleteBadgeClass(string $badgeClassId): bool
    {

        $response = $this->getClient()->delete('/v2/badgeclasses/' . $badgeClassId);

        if (null !== $response && ($response->status() === 200 || $response->status() === 404)) {
            return true;
        }

        return false;
    }

    public function addAssertion(string $badgeClassId, string $recipientIdentifier, string $recipientType = 'email', mixed $issuedOn = null, ?string $evidenceUrl = null, ?string $evidenceNarrative = null): mixed
    {
        $payload = [
            'recipient' => [
                'identity' => $recipientIdentifier,
                'type' => $recipientType
            ]
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

        $response = $this->getClient()->post('/v2/badgeclasses/' . $badgeClassId . '/assertions', $payload);

        return $this->getEntityId($response);
    }
}
