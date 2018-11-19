<?php

namespace Fureev\Socialite\Facades;

use Fureev\Socialite\Contracts\Factory;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Fureev\Socialite\SocialiteManager
 * @mixin  \Fureev\Socialite\SocialiteManager
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
