<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Illuminate\Support\Facades\Log;

class BackpackAssertion extends BadgrRecipientProvider
{
    public function all(): array|bool
    {
        $response = $this->getResult('GET', '/v2/backpack/assertions');

        return $response;
    }

    public function getBySlug(string $entityId): mixed
    {
        $response = $this->getFirstResult('GET', '/v2/backpack/assertions/'.$entityId);

        return $response;
    }

    public function rebake()
    {
        foreach (self::all() as $assertion) {
            if ($this->getEmptyResponse('GET', '/v2/assertions/'.$assertion['entityId'].'/rebake') === false) {
                Log::error('Rebake failed while updating Badgr User email address.');
            }
        }
    }
}
