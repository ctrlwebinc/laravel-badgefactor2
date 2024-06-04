<?php

namespace Ctrlweb\BadgeFactor2\Events;

use App\Models\User;
use Carbon\Carbon;
use Ctrlweb\BadgeFactor2\Models\Badgr\Badge;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BadgeRequestFormAccessed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Carbon $timestamp;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public User $user, public Badge $badge)
    {
        $this->timestamp = Carbon::now();
    }
}
