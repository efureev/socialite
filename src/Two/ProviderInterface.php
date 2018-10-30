<?php

namespace Fureev\Socialite\Two;

/**
 * Interface ProviderInterface
 *
 * @package Fureev\Socialite\Two
 */
interface ProviderInterface
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
     * @return \Fureev\Socialite\Two\User
     */
    public function user();
}
