<?php

namespace Fureev\Socialite\Contracts;

/**
 * Interface Factory
 *
 * @package Fureev\Socialite\Contracts
 */
interface Factory
{
    /**
     * Get an OAuth provider implementation.
     *
     * @param  string  $driver
     * @return \Fureev\Socialite\Contracts\Provider
     */
    public function driver($driver = null);
}
