<?php

namespace Fureev\Socialite\Two;

class GitlabProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://gitlab.com/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://gitlab.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token): array
    {
        $userUrl = 'https://gitlab.com/api/v3/user?access_token=' . $token;

        $response = $this->getHttpClient()->get($userUrl);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->configurable([
            'id' => $user['id'],
            'nickname' => $user['username'],
            'name' => $user['name'],
            'email' => $user['email'],
            'avatar' => $user['avatar_url'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code): array
    {
        return parent::getTokenFields($code) + ['grant_type' => 'authorization_code'];
    }
}
