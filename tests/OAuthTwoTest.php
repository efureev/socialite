<?php

namespace Fureev\Socialite\Tests;

use Fureev\Socialite\Tests\Fixtures\FacebookTestProviderStub;
use Fureev\Socialite\Tests\Fixtures\OAuthTwoTestProviderStub;
use Fureev\Socialite\Two\User;
use GuzzleHttp\ClientInterface;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OAuthTwoTest extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testRedirectGeneratesTheProperIlluminateRedirectResponse()
    {
        $request = Request::create('foo');
        $request->setLaravelSession($session = m::mock(Session::class));
        $session->shouldReceive('put')->once();

        $config = ['client_id', 'client_secret', 'redirect'];
        $provider = new OAuthTwoTestProviderStub($request, $config);
        $response = $provider->redirect();
        $this->assertInstanceOf(SymfonyRedirectResponse::class, $response);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://auth.url', $response->getTargetUrl());
    }

    public function testUserReturnsAUserInstanceForTheAuthenticatedRequest()
    {
        $request = Request::create('foo', 'GET', ['state' => str_repeat('A', 40), 'code' => 'code']);
        $request->setLaravelSession($session = m::mock(Session::class));
        $session->shouldReceive('pull')->once()->with('state')->andReturn(str_repeat('A', 40));
        $config = ['client_id', 'client_secret', 'redirect'];
        $provider = new OAuthTwoTestProviderStub($request, $config);
        $provider->http = m::mock(stdClass::class);
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';
        $provider->http->shouldReceive('post')->once()->with('http://token.url', [
            'headers' => ['Accept' => 'application/json'], $postKey => ['client_id' => 'client_id', 'client_secret' => 'client_secret', 'code' => 'code', 'redirect_uri' => 'redirect_uri'],
        ])->andReturn($response = m::mock(stdClass::class));
        $response->shouldReceive('getBody')->once()->andReturn('{ "access_token" : "access_token", "refresh_token" : "refresh_token", "expires_in" : 3600 }');
        $user = $provider->user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('foo', $user->id);
        $this->assertSame('access_token', $user->token);
        $this->assertSame('refresh_token', $user->refreshToken);
        $this->assertSame(3600, $user->expiresIn);
    }

    public function testUserReturnsAUserInstanceForTheAuthenticatedFacebookRequest()
    {
        $request = Request::create('foo', 'GET', ['state' => str_repeat('A', 40), 'code' => 'code']);
        $request->setSession($session = m::mock(SessionInterface::class));
        $session->shouldReceive('pull')->once()->with('state')->andReturn(str_repeat('A', 40));
        $config = ['client_id', 'client_secret', 'redirect'];
        $provider = new FacebookTestProviderStub($request, $config);
        $provider->http = m::mock(stdClass::class);
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';
        $provider->http->shouldReceive('post')->once()->with('https://graph.facebook.com/v3.0/oauth/access_token', [
            $postKey => ['client_id' => 'client_id', 'client_secret' => 'client_secret', 'code' => 'code', 'redirect_uri' => 'redirect_uri'],
        ])->andReturn($response = m::mock(stdClass::class));
        $response->shouldReceive('getBody')->once()->andReturn(json_encode(['access_token' => 'access_token', 'expires' => 5183085]));
        $user = $provider->user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('foo', $user->id);
        $this->assertSame('access_token', $user->token);
        $this->assertNull($user->refreshToken);
        $this->assertEquals(5183085, $user->expiresIn);
    }

    /**
     * @expectedException \Fureev\Socialite\Two\InvalidStateException
     */
    public function testExceptionIsThrownIfStateIsInvalid()
    {
        $request = Request::create('foo', 'GET', ['state' => str_repeat('B', 40), 'code' => 'code']);
        $request->setLaravelSession($session = m::mock(Session::class));
        $session->shouldReceive('pull')->once()->with('state')->andReturn(str_repeat('A', 40));
        $config = ['client_id', 'client_secret', 'redirect'];
        $provider = new OAuthTwoTestProviderStub($request, $config);
        $provider->user();
    }

    /**
     * @expectedException \Fureev\Socialite\Two\InvalidStateException
     */
    public function testExceptionIsThrownIfStateIsNotSet()
    {
        $request = Request::create('foo', 'GET', ['state' => 'state', 'code' => 'code']);
        $request->setLaravelSession($session = m::mock(Session::class));
        $session->shouldReceive('pull')->once()->with('state');
        $config = ['client_id', 'client_secret', 'redirect'];
        $provider = new OAuthTwoTestProviderStub($request, $config);
        $provider->user();
    }
}
