<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Exception;

class User extends BadgrAdminProvider
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

        return $this->getV1Id('POST', '/v1/user/profile', $payload);
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

        return $this->confirmUpdate('POST', '/v2/users/'.$entityId, $payload);
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
        return $this->getFirstResult('GET', '/v2/users/'.$entityId);
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

        return $this->confirmUpdate('PUT', '/v2/users/'.$entityId, $payload);
    }

    public function getProfile(string $entityId): false|array
    {
        return $this->getFirstResult('GET', '/v2/users/self');
    }

    public function hasVerifiedEmail(string $entityId): bool
    {
        $profile = $this->getProfile($entityId);

        if (false !== $profile && !empty($profile['emails'])) {
            foreach ($profile['emails'] as $email) {
                if (true == $email['verified']) {
                    return true;
                }
            }
        }

        return false;
    }
}
