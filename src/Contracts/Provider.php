<?php

namespace Fureev\Socialite\Contracts;

/**
 * Interface Provider
 *
 * @package Fureev\Socialite\Contracts
 */
interface Provider
{
    /**
     * Redirect the user to the authentication page for the provider.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect();

    /**
     * Get the User instance for the authenticated user.
     *
     * @return \Fureev\Socialite\Contracts\User
     */
    public function user();


    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getLabel(): string;

}
