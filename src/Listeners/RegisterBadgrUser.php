<?php

namespace Ctrlweb\BadgeFactor2\Listeners;

use Ctrlweb\BadgeFactor2\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RegisterBadgrUser implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(UserRegistered $event)
    {
    }
}
