<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

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

    public function import(array $payload): string|bool
    {       
        $response = $this->getEntityId('POST', '/v2/backpack/import', $payload);
        
        return $response;
    }

    public function delete(string $payload): mixed
    {       
        $response = $this->getEntityId('DELETE', '/v2/backpack/assertions/' . $payload, [] );
        
        return $response;
    }
}
