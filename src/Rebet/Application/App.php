<?php
namespace Rebet\Application;

use Rebet\Common\Path;
use Rebet\Config\Config;
use Rebet\Config\ConfigPromise;
use Rebet\Config\Configurable;

/**
 * Application Config Class
 *
 * Define and manage application and framework configuration settings.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class App
{
    use Configurable;

    /**
     * The kernel of this application
     *
     * @var Kernel
     */
    protected static $kernel;

    public static function defaultConfig()
    {
        return [
            'channel'         => null,
            'env'             => null,
            'entry_point'     => null,
            'locale'          => 'en',
            'fallback_locale' => 'en',
            'timezone'        => date_default_timezone_get() ?: 'UTC',
            'resources'       => [
                'i18n' => null,
            ],
            'paginate' => [
                'page_name'        => 'page',
                'page_size_name'   => 'page_size',
                'default_template' => 'paginate@bootstrap-4',
            ],
        ];
    }

    /**
     * Get the application kernel
     *
     * @return Kernel
     */
    public static function kernel() : Kernel
    {
        return static::$kernel ;
    }

    /**
     * Get the application structure.
     *
     * @return Structure
     */
    public static function structure() : Structure
    {
        return static::kernel()->structure();
    }

    /**
     * initialize App and set framework configure.
     *
     * @return void
     */
    public static function init(Kernel $kernel) : void
    {
        static::$kernel = $kernel;
        $kernel->bootstrap();
    }

    /**
     * Get application root path
     *
     * @return string
     */
    public static function root() : string
    {
        return static::kernel()->structure()->root();
    }

    /**
     * Convert application root relative path to absolute path.
     *
     * @param $root_relative_path
     * @return string
     */
    public static function path(string $root_relative_path) : string
    {
        return static::kernel()->structure()->path($root_relative_path);
    }

    /**
     * Get the current locale.
     *
     * @return string
     */
    public static function getLocale() : string
    {
        return self::config('locale');
    }

    /**
     * Get the current fallback locale.
     *
     * @return string
     */
    public static function getFallbackLocale() : string
    {
        return self::config('fallback_locale');
    }

    /**
     * Set the current locale (and fallback locale) by given locale.
     *
     * @param string $locale
     * @param string|null $fallback_locale if null given then do nothing (default: null)
     */
    public static function setLocale(string $locale, ?string $fallback_locale = null) : void
    {
        self::setConfig(['locale' => $locale]);
        if ($fallback_locale !== null) {
            self::setConfig(['fallback_locale' => $fallback_locale]);
        }
    }

    /**
     * Determine whether it is a specific locale.
     *
     * @param string ...$locale
     */
    public static function localeIn(string ...$locale) : bool
    {
        return \in_array(self::getLocale(), $locale, true);
    }

    /**
     * Get the current environment.
     *
     * @return string
     */
    public static function getEnv() : string
    {
        return self::config('env');
    }

    /**
     * Set the current environment by given environment.
     *
     * @param string $env 環境
     */
    public static function setEnv(string $env) : void
    {
        self::setConfig(['env' => $env]);
    }

    /**
     * Determine whether it is a specific environment.
     *
     * @param string ...$env
     */
    public static function envIn(string ...$env) : bool
    {
        return \in_array(self::getEnv(), $env, true);
    }

    /**
     * Get the current channel (inflow route/application invoke interface) like web, api, console.
     *
     * @return string
     */
    public static function getChannel() : string
    {
        return self::config('channel');
    }

    /**
     * Set the current channel (inflow route/application invoke interface) like web, api, console.
     *
     * @param string $channel
     */
    public static function setChannel(string $channel) : void
    {
        self::setConfig(['channel' => $channel]);
    }

    /**
     * Determine whether it is a specific channel (inflow route/application invoke interface) like web, api, console.
     *
     * @param string ...$channel
     */
    public static function channelIn(string ...$channel) : bool
    {
        return \in_array(self::getChannel(), $channel, true);
    }

    /**
     * Get the current entry point name.
     *
     * @return string
     */
    public static function getEntryPoint() : string
    {
        return self::config('entry_point');
    }

    /**
     * Set the current entry point name.
     *
     * @param string $entry_point
     */
    public static function setEntryPoint(string $entry_point) : void
    {
        self::setConfig(['entry_point' => $entry_point]);
    }

    /**
     * It returns the value according to the environment based on the current execution environment.
     * The following can be specified for the key name of $case, and the value is acquired according to the priority of 1 => 4.
     *
     *  1. channel@env
     *  2. channel
     *  3. env
     *  4. default
     *
     * @param array $case
     * @return ConfigPromise
     */
    public static function when(array $case) : ConfigPromise
    {
        return Config::promise(function () use ($case) {
            $channel = App::getChannel();
            $env     = App::getEnv();
            return
                $case["{$channel}@{$env}"] ??
                $case[$channel] ??
                $case[$env] ??
                $case['default']
            ;
        }, false);
    }

    /**
     * Get the current time zone.
     *
     * @return string
     */
    public static function getTimezone() : string
    {
        return self::config('timezone');
    }

    /**
     * Set the time zone.
     *
     * @param string $timezone
     */
    public static function setTimezone(string $timezone) : void
    {
        self::setConfig(['timezone' => $timezone]);
    }
}
