<?php

namespace Fureev\Socialite;

use Fureev\Socialite\Two\CustomProvider;
use Fureev\Socialite\Two\GithubProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Manager;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Php\Support\Exceptions\InvalidConfigException;
use Php\Support\Exceptions\MissingConfigException;

/**
 * Class SocialiteManager
 *
 * @package Fureev\Socialite
 */
class SocialiteManager extends Manager implements Contracts\Factory
{
    /** @var array */
    protected $config;

    /**
     * Get a driver instance.
     *
     * @param  string $driver
     *
     * @return mixed
     */
    public function with($driver)
    {
        return $this->driver($driver);
    }

    /**
     * Config path
     *
     * @return string
     */
    protected static function configSection()
    {
        return 'social';
    }

    /**
     * Get config
     *
     * @param string|null $key
     *
     * @return mixed
     * @throws \Php\Support\Exceptions\InvalidConfigException
     * @throws \Php\Support\Exceptions\MissingConfigException
     */
    public function getConfig($key = null)
    {
        if (!$this->config) {

            if (!$config = $this->app['config'][ static::configSection() ]) {
                throw new MissingConfigException($config, static::configSection());
            }

            if (!is_array($config)) {
                throw new InvalidConfigException($config);
            }

            $this->config = $config;
        }

        return Arr::get($this->config, $key);

    }

    /**
     * Return list of all Providers: driver, customDriver (\Closure)
     *
     * @return \Fureev\Socialite\Two\CustomProvider[]
     */
    public function getProviders()
    {
        $result = $this->getDrivers();

        foreach ($this->customCreators ?? [] as $key => $val) {
            $result[ $key ] = $val;
        }

        return $result;
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return \Fureev\Socialite\Two\AbstractProvider
     * @throws \Php\Support\Exceptions\InvalidConfigException
     * @throws \Php\Support\Exceptions\MissingConfigException
     */
    protected function createGithubDriver()
    {
        return $this->buildProvider(GithubProvider::class, $this->getConfig('drivers.github'));
    }

    /**
     * Create an instance of the specified driver.
     *
     * @param array|null $config
     *
     * @return \Fureev\Socialite\Two\AbstractProvider
     * @throws \Php\Support\Exceptions\InvalidConfigException
     * @throws \Php\Support\Exceptions\MissingConfigException
     */
    /*protected function createGoogleDriver(?array $config = null)
    {
        return $this->buildProvider(GoogleProvider::class, $config ?? $this->getConfig('drivers.google'));
    }*/

    /**
     * Build an OAuth 2 provider instance.
     *
     * @param  string $provider
     * @param  array  $config
     *
     * @return \Fureev\Socialite\Two\AbstractProvider
     */
    public function buildProvider($provider, $config)
    {
        return new $provider($this->app['request'], $this->formatConfig($config));

    }

    /**
     * Format the server configuration.
     *
     * @param  array $config
     *
     * @return array
     */
    public function formatConfig(array $config)
    {

        if (!isset($config['redirectUrl'])) {
            $config['redirectUrl'] = $this->formatUrl(value($config['redirect']));;
        }

        if (!isset($config['callbackUrl'])) {
            $config['callbackUrl'] = $this->formatUrl(value($config['callback']));
        }

        return $config;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    protected function formatUrl(string $url): string
    {
        return Str::startsWith($url, '/')
            ? $this->app['url']->to($url)
            : $url;
    }

    /**
     * Get the default driver name.
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public function getDefaultDriver()
    {
        throw new InvalidArgumentException('No Socialite driver was specified.');
    }


    /**
     * @param array|null $drivers
     *
     * @return $this
     * @throws \Php\Support\Exceptions\InvalidConfigException
     * @throws \Php\Support\Exceptions\MissingConfigException
     */
    public function buildCustomProviders(?array $drivers)
    {
        foreach ($drivers ?? [] as $driverName) {
            $this->buildCustomProvider($driverName);
        }

        return $this;
    }

    /**
     * @param string     $name
     * @param array|null $driverConfig
     *
     * @return CustomProvider|null
     * @throws \Php\Support\Exceptions\InvalidConfigException
     * @throws \Php\Support\Exceptions\MissingConfigException
     */
    public function buildCustomProvider(string $name, array $driverConfig = null)
    {
        if (!$driverConfig) {
            $driverConfig = $this->getConfig('drivers.' . $name);
        }

        $driverConfig['name'] = $name;

        if (isset($driverConfig['enabled']) && $driverConfig['enabled'] === false) {
            return null;
        }

        if (!isset($driverConfig['redirect'])) {
            $driverConfig['redirect'] = $this->getConfig('routes.redirect') . '/' . $name;
        }

        if (!isset($driverConfig['callback'])) {
            $driverConfig['callback'] = $this->getConfig('routes.callback') . '/' . $name;
        }

        if ($this->hasBuildInDriver($name)) {
            $this->drivers[ $name ] = $this->{static::buildInDriverMethodName($name)}($driverConfig);
        } else {
            $provider = (!empty($driverConfig['provider']) && class_exists((string)$driverConfig['provider']))
                ? $driverConfig['provider']
                : CustomProvider::class;

            $this->drivers[ $name ] = $this->buildProvider($provider, $driverConfig);
        }

        return $this->drivers[ $name ];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasBuildInDriver(string $name): bool
    {
        return method_exists($this, static::buildInDriverMethodName($name));
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function buildInDriverMethodName(string $name): string
    {
        return 'create' . Str::studly($name) . 'Driver';
    }

    /**
     * @param string $driver
     *
     * @return \Fureev\Socialite\Two\CustomProvider|mixed|null
     * @throws \Php\Support\Exceptions\InvalidConfigException
     * @throws \Php\Support\Exceptions\MissingConfigException
     */
    protected function createDriver($driver)
    {
        if (isset($this->customCreators[ $driver ])) {
            return $this->callCustomCreator($driver);
        }

        return $this->buildCustomProvider($driver);
    }


    /**
     * Create an instance of the specified driver.
     *
     * @return \Fureev\Socialite\Two\AbstractProvider
     */
    /*protected function createFacebookDriver()
    {
        $config = $this->app['config']['services.facebook'];

        return $this->buildProvider(
            FacebookProvider::class, $config
        );
    }*/


    /**
     * Create an instance of the specified driver.
     *
     * @return \Fureev\Socialite\Two\AbstractProvider
     */
    /*  protected function createLinkedinDriver()
      {
          $config = $this->app['config']['services.linkedin'];

          return $this->buildProvider(
              LinkedInProvider::class, $config
          );
      }*/

    /**
     * Create an instance of the specified driver.
     *
     * @return \Fureev\Socialite\Two\AbstractProvider
     */
    /* protected function createBitbucketDriver()
     {
         $config = $this->app['config']['services.bitbucket'];

         return $this->buildProvider(
             BitbucketProvider::class, $config
         );
     }*/

    /**
     * Create an instance of the specified driver.
     *
     * @return \Fureev\Socialite\Two\AbstractProvider
     */
    /* protected function createGitlabDriver()
     {
         $config = $this->app['config']['services.gitlab'];

         return $this->buildProvider(
             \Fureev\Socialite\Two\GitlabProvider::class, $config
         );
     }*/


}
