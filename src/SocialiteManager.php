<?php

namespace Fureev\Socialite;

use Fureev\Socialite\Two\AbstractProvider;
use Fureev\Socialite\Two\CustomProvider;
use Fureev\Socialite\Two\GithubProvider;
use Fureev\Socialite\Two\VkProvider;
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
    protected $configSocial;

    /**
     * Get a driver instance.
     *
     * @param string $driver
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

        if (!$this->configSocial) {
            if (!$config = $this->app['config'][static::configSection()]) {
                throw new MissingConfigException($config, static::configSection());
            }

            if (!is_array($config)) {
                throw new InvalidConfigException($config);
            }

            $this->configSocial = $config;
        }

        return Arr::get($this->configSocial, $key);

    }

    /**
     * Return list of all Providers: driver, customDriver (\Closure)
     *
     * @param bool $valid
     *
     * @return AbstractProvider[]
     */
    public function getProviders($valid = true): array
    {
        $result = $this->{$valid ? 'getValidDrivers' : 'getDrivers'}();

        foreach ($this->customCreators ?? [] as $key => $val) {
            if ($val->valid()) {
                $result[$key] = $val;
            }
        }

        return $result;
    }

    /**
     * Return list of all valid drivers
     *
     * @return array
     */
    public function getValidDrivers(): array
    {
        return collect($this->getDrivers())->filter->valid()->toArray();
    }


    /**
     * Create an instance of the specified driver.
     *
     * @param array|null $config
     *
     * @return AbstractProvider
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
     * @param string $provider
     * @param array $config
     *
     * @return AbstractProvider
     */
    public function buildProvider($provider, $config): AbstractProvider
    {
        return new $provider($this->app['request'], $this->formatConfig($config));

    }

    /**
     * Format the server configuration.
     *
     * @param array $config
     *
     * @return array
     */
    public function formatConfig(array $config): array
    {
        if (!isset($config['redirectUrl'])) {
//            dd($config);
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
     * @return string
     * @throws \InvalidArgumentException
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
     * @param string $name
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
            $this->drivers[$name] = $this->{static::buildInDriverMethodName($name)}($driverConfig);
        } else {
            $provider = (!empty($driverConfig['provider']) && class_exists((string)$driverConfig['provider']))
                ? $driverConfig['provider']
                : CustomProvider::class;

            $this->drivers[$name] = $this->buildProvider($provider, $driverConfig);
        }

        return $this->drivers[$name];
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
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        return $this->buildCustomProvider($driver);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
     * @throws \Php\Support\Exceptions\InvalidConfigException
     * @throws \Php\Support\Exceptions\MissingConfigException
     */
    protected function createGithubDriver($driverConfig = null)
    {
        return $this->buildProvider(GithubProvider::class, $driverConfig ?? $this->getConfig('drivers.github'));
    }

    /**
     * @return AbstractProvider
     * @throws \Php\Support\Exceptions\InvalidConfigException
     * @throws \Php\Support\Exceptions\MissingConfigException
     */
    protected function createVkDriver($driverConfig = null)
    {
        return $this->buildProvider(VkProvider::class, $driverConfig ?? $this->getConfig('drivers.vk'));
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return AbstractProvider
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
     * @return AbstractProvider
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
     * @return AbstractProvider
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
     * @return AbstractProvider
     */
    /* protected function createGitlabDriver()
     {
         $config = $this->app['config']['services.gitlab'];

         return $this->buildProvider(
             \Fureev\Socialite\Two\GitlabProvider::class, $config
         );
     }*/


}
