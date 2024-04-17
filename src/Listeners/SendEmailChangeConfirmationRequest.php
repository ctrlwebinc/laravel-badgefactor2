<?php

namespace Ctrlweb\BadgeFactor2\Listeners;

use Ctrlweb\BadgeFactor2\Events\EmailChangeRequested;
use Ctrlweb\BadgeFactor2\Notifications\ChangeEmailNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEmailChangeConfirmationRequest implements ShouldQueue
{

    /**
     * Handle the event.
     *
     * @param object $event
     *
     * @return void
     */
    public function handle(EmailChangeRequested $event)
    {
        $event->user->notify(new ChangeEmailNotification());
    }
}
