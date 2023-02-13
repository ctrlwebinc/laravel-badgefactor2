<?php

namespace Ctrlweb\BadgeFactor2\Badgr;

use Exception;

class BadgrProvider
{

    private BadgrClient $client;

    public function __construct(BadgrClient $client)
    {
        $this->client = $client;
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
     * @throws Exception
     */
    public function getClient()
    {
        return $this->client->getHttpClient();
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

        if (null !== $response && $response->status() === 200) {
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
    public function checkUserVerified(string $entityId): bool
    {

        $response = $this->getClient()->get('/v2/users/' . $entityId);

        if (null !== $response && $response->status() === 200) {
            $response = $response->json();

            if (isset($response['status']['success']) && true === $response['status']['success'] &&
                isset($response['result'][0]) && isset($response['result'][0]->recipient)) {
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
}
