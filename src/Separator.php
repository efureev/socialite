<?php

namespace Fureev\Socialite;

/**
 * Class Separator
 *
 * @package Fureev\Socialite
 */
class Separator
{
    /** @var string */
    protected $value;

    public function __construct($val = ' ')
    {
        if (is_callable($val)) {
            $this->value = $val();
        } else {
            $this->value = $val;
        }
    }

    public function __toString()
    {
        return $this->value;
    }
}
