<?php

namespace Fureev\Socialite\Two;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Arr;

class FacebookProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The base Facebook Graph URL.
     *
     * @var string
     */
    protected $graphUrl = 'https://graph.facebook.com';

    /**
     * The Graph API version for the request.
     *
     * @var string
     */
    protected $version = 'v3.3';

    /**
     * The user fields being requested.
     *
     * @var array
     */
    protected $fields = ['name', 'email', 'gender', 'verified', 'link'];

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['email'];

    /**
     * Display the dialog in a popup view.
     *
     * @var bool
     */
    protected $popup = false;

    /**
     * Re-request a declined permission.
     *
     * @var bool
     */
    protected $reRequest = false;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://www.facebook.com/' . $this->version . '/dialog/oauth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return $this->graphUrl . '/' . $this->version . '/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code): array
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            $postKey => $this->getTokenFields($code),
        ]);

        $data = json_decode($response->getBody(), true);

        return Arr::add($data, 'expires_in', Arr::pull($data, 'expires'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token): array
    {
        $meUrl = $this->graphUrl . '/' . $this->version . '/me?access_token=' . $token . '&fields=' . implode(',', $this->fields);

        if (!empty($this->clientSecret)) {
            $appSecretProof = hash_hmac('sha256', $token, $this->clientSecret);

            $meUrl .= '&appsecret_proof=' . $appSecretProof;
        }

        $response = $this->getHttpClient()->get($meUrl, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $avatarUrl = $this->graphUrl . '/' . $this->version . '/' . $user['id'] . '/picture';

        return (new User)->setRaw($user)->configurable([
            'id' => $user['id'], 'nickname' => null, 'name' => isset($user['name']) ? $user['name'] : null,
            'email' => isset($user['email']) ? $user['email'] : null, 'avatar' => $avatarUrl . '?type=normal',
            'avatar_original' => $avatarUrl . '?width=1920',
            'profileUrl' => isset($user['link']) ? $user['link'] : null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null): array
    {
        $fields = parent::getCodeFields($state);

        if ($this->popup) {
            $fields['display'] = 'popup';
        }

        if ($this->reRequest) {
            $fields['auth_type'] = 'rerequest';
        }

        return $fields;
    }

    /**
     * Set the user fields to request from Facebook.
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

    /**
     * Set the dialog to be displayed as a popup.
     *
     * @return $this
     */
    public function asPopup(): self
    {
        $this->popup = true;

        return $this;
    }

    /**
     * Re-request permissions which were previously declined.
     *
     * @return $this
     */
    public function reRequest(): self
    {
        $this->reRequest = true;

        return $this;
    }

    /**
     * Specify which graph version should be used.
     *
     * @param  string  $version
     * @return $this
     */
    public function usingGraphVersion(string $version)
    {
        $this->version = $version;

        return $this;
    }
}
