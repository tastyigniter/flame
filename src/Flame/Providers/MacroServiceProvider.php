<?php

namespace Igniter\Flame\Providers;

use Igniter\Flame\Mixins\StringMixin;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class MacroServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot()
    {
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
