<?php

namespace Ctrlweb\BadgeFactor2\Events;

use Ctrlweb\BadgeFactor2\Models\Badgr\Assertion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssertionIssued
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $assertion;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Assertion $assertion)
    {
        $this->assertion = $assertion;
    }
}