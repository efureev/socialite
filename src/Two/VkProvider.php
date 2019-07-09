<?php

namespace Fureev\Socialite\Two;

use Illuminate\Support\Arr;

class VkProvider extends AbstractProvider implements ProviderInterface
{
    const VERSION = '5.101';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['email'];


    /**
     * The fields that are included in the profile.
     *
     * @var array
     */
    protected $fields = ['id', 'email', 'first_name', 'last_name', 'screen_name', 'photo'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://oauth.vk.com/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://oauth.vk.com/access_token';
    }

    /**
     * @return \Fureev\Socialite\Contracts\User|\Fureev\Socialite\Two\AbstractProvider|\Fureev\Socialite\Two\User
     * @throws \Exception
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $token = Arr::get($response, 'access_token');

        $user = $this->mapUserToObject($this->getUserByToken($response));

        return $user->setToken($token)
            ->setRefreshToken(Arr::get($response, 'refresh_token'))
            ->setExpiresIn(Arr::get($response, 'expires_in'));
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $token
     *
     * @return array
     * @throws \Exception
     */
    protected function getUserByToken($token): array
    {
        $from_token = [];

        if (\is_array($token)) {
            $from_token['email'] = $token['email'];
            $token = $token['access_token'];
        }

        $params = http_build_query([
            'access_token' => $token,
            'fields' => implode(',', $this->fields),
            'language' => $this->getDriverConfig('lang', 'en'),
            'v' => self::VERSION,
        ]);

        $response = $this->getHttpClient()->get('https://api.vk.com/method/users.get?' . $params);

        $response = \json_decode($response->getBody(), true);

        if (!is_array($response) || !isset($response['response'][0])) {
            throw new \RuntimeException(sprintf(
                'Invalid JSON response from VK: %s',
                $response->getBody()
            ));
        }

        return array_merge($from_token, $response['response'][0]);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->configurable([
            'id' => Arr::get($user, 'id'),
            'nickname' => Arr::get($user, 'screen_name'),
            'name' => trim(Arr::get($user, 'first_name') . ' ' . Arr::get($user, 'last_name')),
            'email' => Arr::get($user, 'email'),
            'avatar' => Arr::get($user, 'photo'),
        ]);
    }

    /**
     * @param string $code
     *
     * @return array
     */
    protected function getTokenFields(string $code): array
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * Set the user fields to request from LinkedIn.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function fields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }
}
