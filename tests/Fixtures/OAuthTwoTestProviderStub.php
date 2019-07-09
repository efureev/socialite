<?php

namespace Fureev\Socialite\Tests\Fixtures;

use Fureev\Socialite\Two\AbstractProvider;
use Fureev\Socialite\Two\User;
use Mockery as m;
use stdClass;

class OAuthTwoTestProviderStub extends AbstractProvider
{
    /**
     * @var \GuzzleHttp\Client|\Mockery\MockInterface
     */
    public $http;

    protected function getAuthUrl($state): string
    {
        return 'http://auth.url';
    }

    protected function getTokenUrl(): string
    {
        return 'http://token.url';
    }

    protected function getUserByToken($token): array
    {
        return ['id' => 'foo'];
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->map(['id' => $user['id']]);
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
