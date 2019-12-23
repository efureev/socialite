<?php

namespace Fureev\Socialite;

use Fureev\Socialite\Contracts\Factory;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Class SocialiteServiceProvider
 *
 * @package Fureev\Socialite
 */
class SocialiteServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [Factory::class];
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(Factory::class, static function ($app) {
            return new SocialiteManager($app);
        });
    }
}
