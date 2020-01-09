<?php

namespace Fureev\Socialite\Two;

use Fureev\Socialite\Separator;
use Php\Support\Helpers\Arr;
use Php\Support\Helpers\Json;

/**
 * Class CustomProvider
 *
 * @package Fureev\Socialite\Two
 */
class CustomProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getDriverConfig('url_auth'), $state);
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    protected function getTokenUrl(): string
    {
        return $this->getDriverConfig('url_token');
    }


    /**
     * @param array $user
     * {@inheritdoc}
     *
     * @return \Fureev\Socialite\Two\User
     * @throws \Exception
     */
    protected function mapUserToObject(array $user): User
    {
        $fields = $this->getDriverConfig('mapFields');
        array_walk($fields, static function (&$val, $k) use ($user) {
            if (is_array($val)) {
                $newVal = [];
                foreach ($val as $v) {
                    $newVal[] = $v instanceof Separator ? (string)$v : Arr::get($user, $v);
                }
                $val = implode('', $newVal);
            } else {
                $val = Arr::get($user, $val);
            }

        }, $user);

        return (new User)->setRaw($user)->configurable($fields);
    }

    /**
     * @param string $token
     *
     * @return array|mixed
     * @throws \Exception
     */
    protected function getUserByToken($token): array
    {
        $this->guzzle = Arr::replaceByTemplate($this->guzzle, ['{{%TOKEN%}}' => $token]);

        $response = $this->getHttpClient()->get($this->getDriverConfig('userInfoUrl'), $this->guzzle);

        return Json::decode($response->getBody());
    }

}
