<?php

namespace Ctrlweb\BadgeFactor2\Services\Badgr;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;

class BackpackAssertion extends BadgrRecipientProvider
{
    public function all(): array|bool
    {
        $response = $this->getResult('GET','/v2/backpack/assertions');

        return $response;
    }
}
