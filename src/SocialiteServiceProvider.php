<?php

namespace Fureev\Socialite;

use Fureev\Socialite\Contracts\Factory;
use Illuminate\Support\ServiceProvider;

/**
 * Class SocialiteServiceProvider
 *
 * @package Fureev\Socialite
 */
class SocialiteServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

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
        $this->app->singleton(Factory::class, function ($app) {
            return new SocialiteManager($app);
        });
    }
}
