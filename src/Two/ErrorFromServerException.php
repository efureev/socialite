<?php

namespace Fureev\Socialite\Two;

use RuntimeException;

/**
 * Class ErrorFromServerException
 *
 * @package Fureev\Socialite\Two
 */
class ErrorFromServerException extends RuntimeException
{
    /** @var string|null */
    public $description;

    /** @var string|null */
    public $uri;

    /**
     * ErrorFromServerException constructor.
     *
     * @param string $error Short name of error
     * @param string|null $description Error description
     * @param string|null $uri Error URL
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $error, ?string $description = null, ?string $uri = null, $code = 0, \Throwable $previous = null)
    {
        $this->description = $description;
        $this->uri = $uri;

        parent::__construct($error, $code, $previous);
    }
}
