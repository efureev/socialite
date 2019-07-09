<?php

namespace Fureev\Socialite\Two;

use Fureev\Socialite\AbstractUser;

/**
 * Class User
 *
 * @package Fureev\Socialite\Two
 */
class User extends AbstractUser
{
    /**
     * The user's access token.
     *
     * @var string
     */
    public $token;

    /**
     * The refresh token that can be exchanged for a new access token.
     *
     * @var string
     */
    public $refreshToken;

    /**
     * The number of seconds the access token is valid for.
     *
     * @var int
     */
    public $expiresIn;

    /**
     * Set the token on the user.
     *
     * @param string $token
     *
     * @return $this
     */
    public function setToken($token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Set the refresh token required to obtain a new access token.
     *
     * @param string $refreshToken
     *
     * @return $this
     */
    public function setRefreshToken($refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    /**
     * Set the number of seconds the access token is valid for.
     *
     * @param int $expiresIn
     *
     * @return $this
     */
    public function setExpiresIn($expiresIn): self
    {
        $this->expiresIn = $expiresIn;

        return $this;
    }
}
