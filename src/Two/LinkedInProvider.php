<?php

namespace Fureev\Socialite\Two;

use Illuminate\Support\Arr;

class LinkedInProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['r_liteprofile', 'r_emailaddress'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * The fields that are included in the profile.
     *
     * @var array
     */
    protected $fields = [
        'id', 'first-name', 'last-name', 'formatted-name',
        'email-address', 'headline', 'location', 'industry',
        'public-profile-url', 'picture-url', 'picture-urls::(original)',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://www.linkedin.com/oauth/v2/authorization', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://www.linkedin.com/oauth/v2/accessToken';
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     *
     * @return array
     */
    protected function getTokenFields(string $code): array
    {
        return parent::getTokenFields($code) + ['grant_type' => 'authorization_code'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token): array
    {
        $basicProfile = $this->getBasicProfile($token);
        $emailAddress = $this->getEmailAddress($token);

        return array_merge($basicProfile, $emailAddress);
    }

    /**
     * @param $token
     *
     * @return array
     */
    protected function getBasicProfile($token)
    {
        $url = 'https://api.linkedin.com/v2/me?projection=(id,firstName,lastName,profilePicture(displayImage~:playableStreams))';
        $response = $this->getHttpClient()->get($url, [
            'headers' => [
                'X-RestLi-Protocol-Version' => '2.0.0',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
        return (array)json_decode($response->getBody(), true);
    }

    /**
     * @param $token
     *
     * @return array
     */
    protected function getEmailAddress($token)
    {
        $url = 'https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))';
        $response = $this->getHttpClient()->get($url, [
            'headers' => [
                'X-RestLi-Protocol-Version' => '2.0.0',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
        return (array)Arr::get((array)json_decode($response->getBody(), true), 'elements.0.handle~');
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->configurable([
            'id' => $user['id'], 'nickname' => null, 'name' => Arr::get($user, 'formattedName'),
            'email' => Arr::get($user, 'emailAddress'), 'avatar' => Arr::get($user, 'pictureUrl'),
            'avatar_original' => Arr::get($user, 'pictureUrls.values.0'),
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
