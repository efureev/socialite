<?php

namespace Fureev\Socialite\Tests\Fixtures;

use Fureev\Socialite\Two\FacebookProvider;
use Mockery as m;
use stdClass;

class FacebookTestProviderStub extends FacebookProvider
{
    /**
     * @var \GuzzleHttp\Client|\Mockery\MockInterface
     */
    public $http;

    protected function getUserByToken($token): array
    {
        return ['id' => 'foo'];
    }

    /**
     * Get a fresh instance of the Guzzle HTTP client.
     *
     * @return \GuzzleHttp\Client|\Mockery\MockInterface
     */
    protected function getHttpClient()
    {
        if ($this->http) {
            return $this->http;
        }
        return $this->http = m::mock(stdClass::class);
    }
}
