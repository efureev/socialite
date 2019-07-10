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

    /**
     * ErrorFromServerException constructor.
     *
     * @param string $error
     * @param string|null $description
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $error, ?string $description = null, $code = 0, \Throwable $previous = null)
    {
        $this->description = $description;

        parent::__construct($error, $code, $previous);
    }
}
