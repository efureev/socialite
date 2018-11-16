<?php

namespace Fureev\Socialite;


use ArrayAccess;
use Php\Support\Traits\ConfigurableTrait;

/**
 * Class AbstractUser
 *
 * @package Fureev\Socialite
 */
abstract class AbstractUser implements ArrayAccess, Contracts\User
{
    use ConfigurableTrait;

    /**
     * The unique identifier for the user.
     *
     * @var mixed
     */
    public $id;

    /**
     * The user's nickname / username.
     *
     * @var string|null
     */
    public $nickname;

    /**
     * The user's full name.
     *
     * @var string
     */
    public $name;

    /**
     * The user's e-mail address.
     *
     * @var string
     */
    public $email;

    /**
     * The user's avatar image URL.
     *
     * @var string
     */
    public $avatar;

    /**
     * The user's raw attributes.
     *
     * @var array
     */
    public $raw;

    /**
     * Get the unique identifier for the user.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the nickname / username for the user.
     *
     * @return string|null
     */
    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    /**
     * Get the full name of the user.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the e-mail address of the user.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get the avatar / image URL for the user.
     *
     * @return string|null
     */
    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    /**
     * Get the raw user array.
     *
     * @return array
     */
    public function getRaw(): array
    {
        return $this->raw;
    }

    /**
     * Set the raw user array from the provider.
     *
     * @param  array $raw
     *
     * @return $this
     */
    public function setRaw(array $raw)
    {
        $this->raw = $raw;

        return $this;
    }

    /**
     * Determine if the given raw user attribute exists.
     *
     * @param  string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->raw);
    }

    /**
     * Get the given key from the raw user.
     *
     * @param  string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->raw[ $offset ];
    }

    /**
     * Set the given attribute on the raw user array.
     *
     * @param  string $offset
     * @param  mixed  $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->raw[ $offset ] = $value;
    }

    /**
     * Unset the given value from the raw user array.
     *
     * @param  string $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->raw[ $offset ]);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->raw[ $key ])) {
            return $this->raw[ $key ];
        }

        return null;
    }
}
