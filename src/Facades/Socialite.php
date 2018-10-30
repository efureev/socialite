<?php

namespace Fureev\Socialite\Facades;

use Illuminate\Support\Facades\Facade;
use Fureev\Socialite\Contracts\Factory;

/**
 * @see \Fureev\Socialite\SocialiteManager
 */
class Socialite extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Factory::class;
    }
}
