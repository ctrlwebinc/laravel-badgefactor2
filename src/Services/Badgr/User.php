<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Exception;

class User extends BadgrProvider
{
    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $password
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function add(string $firstName, string $lastName, string $email, string $password): mixed
    {
        $payload = [
            'first_name'           => $firstName,
            'last_name'            => $lastName,
            'email'                => $email,
            'url'                  => '',
            'telephone'            => '',
            'slug'                 => '',
            'agreed_terms_version' => 1,
            'marketing_opt_in'     => false,
            'has_password_set'     => false,
            'source'               => 'bf2',
            'password'             => $password,
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
     *
     * @throws Exception
     *
     * @return bool
     */
    public function changePassword(string $entityId, string $oldPassword, string $newPassword): bool
    {
        $payload = [
            'password'        => $newPassword,
            'currentPassword' => $oldPassword,
        ];

        $response = $this->getClient()->post('/v2/users/'.$entityId, $payload);

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
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get(string $entityId): mixed
    {
        $response = $this->getClient()->get('/v2/users/'.$entityId);

        return $this->getFirstResult($response);
    }

    /**
     * @param string $entityId
     *
     * @throws Exception
     *
     * @return bool
     */
    public function checkVerified(string $entityId): bool
    {
        $response = $this->getClient()->get('/v2/users/'.$entityId);

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
     *
     * @throws Exception
     *
     * @return bool
     */
    public function update(string $entityId, string $firstName, string $lastName, string $email): bool
    {
        $payload = [
            'firstName' => $firstName,
            'lastName'  => $lastName,
            'emails'    => [
                [
                    'email'   => $email,
                    'primary' => true,
                ],
            ],
        ];

        $response = $this->getClient()->put('/v2/users/'.$entityId, $payload);

        if (null !== $response && $response->status() === 200) {
            return true;
        }

        return false;
    }
}
