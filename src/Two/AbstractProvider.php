<?php

namespace Fureev\Socialite\Two;

use Fureev\Socialite\Contracts\Provider as ProviderContract;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Php\Support\Traits\ConfigurableTrait;

/**
 * Class AbstractProvider
 *
 * @package Fureev\Socialite\Two
 */
abstract class AbstractProvider implements ProviderContract
{
    use ConfigurableTrait;

    /**
     * Enable Driver
     *
     * @var bool
     */
    public $enabled = true;

    /**
     * The name of this driver
     *
     * @var string
     */
    protected $name;

    /**
     * The config
     *
     * @var array
     */
    protected $config;

    /**
     * The HTTP request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The HTTP Client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * The client ID.
     *
     * @var string
     */
    protected $clientId;

    /**
     * The client secret.
     *
     * @var string
     */
    protected $clientSecret;

    /**
     * The redirect URL.
     *
     * @var string
     */
    protected $redirectUrl;


    /**
     * The callback URL from service
     *
     * @var string
     */
    protected $callbackUrl;


    /**
     * The user info URL at service
     *
     * @var string
     */
    protected $userInfoUrl;

    /**
     * The custom parameters to be sent with the request.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ',';

    /**
     * The type of the encoding in the query.
     *
     * @var int Can be either PHP_QUERY_RFC3986 or PHP_QUERY_RFC1738.
     */
    protected $encodingType = PHP_QUERY_RFC1738;

    /**
     * Indicates if the session state should be utilized.
     *
     * @var bool
     */
    protected $stateless = false;

    /**
     * The custom Guzzle configuration options.
     *
     * @var array
     */
    protected $guzzle = [];

    /**
     * Create a new provider instance.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $config
     *
     * @return void
     */
    public function __construct(Request $request, array $config = [])
    {
        $this->request = $request;
        $this->setDriverConfig($config);
        $this->configurable($config, false);
    }

    /**
     * Set driver name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     *
     * @return mixed
     * @throws \Exception
     */
    public function getDriverConfig(?string $key = null, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * @param array $config
     */
    protected function setDriverConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     *
     * @return string
     */
    abstract protected function getAuthUrl($state): string;

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    abstract protected function getTokenUrl(): string;

    /**
     * Get the raw user for the given access token.
     *
     * @param string $token
     *
     * @return array
     */
    abstract protected function getUserByToken($token): array;

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param array $user
     *
     * @return \Fureev\Socialite\Two\User
     */
    abstract protected function mapUserToObject(array $user);

    /**
     * Redirect the user of the application to the provider's authentication screen.
     *
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function redirect()
    {
        $state = null;

        if ($this->usesState()) {
            $this->request->session()->put('state', $state = $this->getState());
        }

        return new RedirectResponse($this->getAuthUrl($state));
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $url
     * @param string $state
     *
     * @return string
     */
    protected function buildAuthUrlFromBase($url, $state)
    {
        return $url . '?' . http_build_query($this->getCodeFields($state), '', '&', $this->encodingType);
    }

    /**
     * Get the GET parameters for the code request.
     *
     * @param string|null $state
     *
     * @return array
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->callbackUrl,
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'response_type' => 'code',
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * Format the given scopes.
     *
     * @param array $scopes
     * @param string $scopeSeparator
     *
     * @return string
     */
    protected function formatScopes(array $scopes, $scopeSeparator)
    {
        return implode($scopeSeparator, $scopes);
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        $this->checkOnError();

        $response = $this->getAccessTokenResponse($this->getCode());

        $user = $this->mapUserToObject($this->getUserByToken(
            $token = Arr::get($response, 'access_token')
        ));

        return $user->setToken($token)
            ->setRefreshToken(Arr::get($response, 'refresh_token'))
            ->setExpiresIn(Arr::get($response, 'expires_in'));
    }

    /**
     * Get a Social User instance from a known access token.
     *
     * @param string $token
     *
     * @return \Fureev\Socialite\Two\User
     */
    public function userFromToken($token)
    {
        $user = $this->mapUserToObject($this->getUserByToken($token));

        return $user->setToken($token);
    }

    /**
     * Determine if the current request / session has a mismatching "state".
     *
     * @return bool
     */
    protected function hasInvalidState(): bool
    {
        if ($this->isStateless()) {
            return false;
        }

        $state = $this->request->session()->pull('state');

        return !($state !== '' && $this->request->input('state') === $state);
    }

    /**
     * Check for errors
     */
    protected function checkOnError(): void
    {
        $key = $this->getDriverConfig('errorKey', 'error');

        if ($error = $this->request->query($key)) {
            throw new ErrorFromServerException($error,
                $this->request->query($this->getDriverConfig('errorDescriptionKey', 'error_description')),
                $this->request->query($this->getDriverConfig('errorUriKey', 'error_uri'))
            );
        }
    }

    /**
     * Get the access token response for the given code.
     *
     * @param string $code
     *
     * @return array
     */
    public function getAccessTokenResponse($code): array
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            $postKey => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    protected $tokenFieldsExtra = [];

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     *
     * @return array
     */
    protected function getTokenBaseFields(?string $code): array
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->callbackUrl,
        ];
    }

    /**
     * @param string $code
     *
     * @return array
     */
    protected function getTokenFields(?string $code): array
    {
        $data = $this->getTokenBaseFields($code);

        if (is_array($this->tokenFieldsExtra)) {
            $array = $this->tokenFieldsExtra;
            array_walk($array, static function ($v, $k) use (&$data) {
                $data = Arr::add($data, $k, $v);
            });
        }

        return $data;
    }

    /**
     * Get the code from the request.
     *
     * @return string
     */
    protected function getCode(): ?string
    {
        return $this->request->query('code');
    }

    /**
     * Merge the scopes of the requested access.
     *
     * @param array|string $scopes
     *
     * @return $this
     */
    public function scopes($scopes): self
    {
        $this->scopes = array_unique(array_merge($this->scopes, (array)$scopes));

        return $this;
    }

    /**
     * Set the scopes of the requested access.
     *
     * @param array|string $scopes
     *
     * @return $this
     */
    public function setScopes($scopes): self
    {
        $this->scopes = array_unique((array)$scopes);

        return $this;
    }

    /**
     * Get the current scopes.
     *
     * @return array
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Set the redirect URL.
     *
     * @param string $url
     *
     * @return $this
     */
    public function redirectUrl($url): self
    {
        $this->redirectUrl = $url;

        return $this;
    }

    /**
     * Get the redirect URL.
     *
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getLabel(): string
    {
        return $this->getDriverConfig('label') ?? $this->name;
    }

    /**
     * Get a instance of the Guzzle HTTP client.
     *
     * @return Client
     */
    protected function getHttpClient()
    {
        if ($this->httpClient === null) {
            $this->httpClient = new Client($this->guzzle);
        }

        return $this->httpClient;
    }

    /**
     * Set the Guzzle HTTP client instance.
     *
     * @param Client $client
     *
     * @return $this
     */
    public function setHttpClient(Client $client): self
    {
        $this->httpClient = $client;

        return $this;
    }

    /**
     * Set the request instance.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return $this
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Determine if the provider is operating with state.
     *
     * @return bool
     */
    protected function usesState(): bool
    {
        return !$this->stateless;
    }

    /**
     * Determine if the provider is operating as stateless.
     *
     * @return bool
     */
    protected function isStateless(): bool
    {
        return $this->stateless;
    }

    /**
     * Indicates that the provider should operate as stateless.
     *
     * @return $this
     */
    public function stateless(): self
    {
        $this->stateless = true;

        return $this;
    }

    /**
     * Get the string used for session state.
     *
     * @return string
     * @throws \Exception
     */
    protected function getState(): string
    {
        return Str::random(40);
    }

    /**
     * Set the custom parameters of the request.
     *
     * @param array $parameters
     *
     * @return $this
     */
    public function with(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }
}
