<?php

namespace Igniter\Flame\Providers;

use Igniter\Flame\Mixins\Mail as MailMixin;
use Igniter\Flame\Mixins\Router;
use Igniter\Flame\Mixins\StringMixin;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class MacroServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot()
    {
        $this->callAfterResolving('mail.manager', function () {
            Mail::mixin(new MailMixin);
        });

        Route::mixin(new Router);

        Str::mixin(new StringMixin);

        Event::macro('fire', function ($event, $payload = [], $halt = false) {
            return $this->dispatch($event, $payload, $halt);
        });
    }

    public function provides()
    {
        return [
            'mail.manager',
            'router',
            'events',
        ];
    }
}
