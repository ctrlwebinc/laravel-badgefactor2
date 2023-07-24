<?php

namespace Ctrlweb\BadgeFactor2\Providers;

use Ctrlweb\BadgeFactor2\Events\UserRegistered;
use Ctrlweb\BadgeFactor2\Listeners\RegisterBadgrUser;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserRegistered::class => [
            RegisterBadgrUser::class,
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
