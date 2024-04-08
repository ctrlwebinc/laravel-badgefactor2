<?php

namespace Ctrlweb\BadgeFactor2\Events;

use Ctrlweb\BadgeFactor2\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailChangeValidated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public int $userId, public string $oldEmail, public string $newEmail)
    {
    }

}
