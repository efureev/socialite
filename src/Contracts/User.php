<?php

namespace Fureev\Socialite\Contracts;

/**
 * Interface User
 *
 * @package Fureev\Socialite\Contracts
 */
interface User
{
    /**
     * Get the unique identifier for the user.
     *
     * @return string
     */
    public function getId();

    /**
     * Get the nickname / username for the user.
     *
     * @return string
     */
    public function getNickname(): string;

    /**
     * Get the full name of the user.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the e-mail address of the user.
     *
     * @return string
     */
    public function getEmail(): string;

    /**
     * Get the avatar / image URL for the user.
     *
     * @return null|string
     */
    public function getAvatar(): ?string;

    /**
     * @return array
     */
    public function getRaw(): array;


}
