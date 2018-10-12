<?php
namespace Rebet\Foundation;

use Rebet\Config\Configurable;
use Rebet\Config\Config;
use Rebet\File\Files;
use Rebet\DateTime\DateTime;
use Rebet\Routing\MethodRoute;
use Rebet\Routing\ConventionalRoute;
use Rebet\Routing\ControllerRoute;
use Rebet\View\View;
use Rebet\View\Engine\Blade\Blade;
use Rebet\Foundation\View\Engine\Blade\BladeCustom;

/**
 * Application Config Class
 *
 * Define and manage application and framework configuration settings.
 *
 * @todo Create an English resource and change the library default setting to ja => en
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class App
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'surface'         => null,
            'env'             => Config::promise(function () {
                return getenv('APP_ENV') ?: 'development' ;
            }),
            'entry_point'     => null,
            'root'            => null,
            'locale'          => 'ja',
            'fallback_locale' => 'ja',
            'timezone'        => date_default_timezone_get() ?: 'UTC',
            'namespace'       => [
                'controller'  => null,
            ],
        ];
    }

    /**
     * initialize framework config.
     *
     * @return void
     */
    public static function initFrameworkConfig() : void
    {
        Config::framework([
            //---------------------------------------------
            // DateTime Configure
            //---------------------------------------------
            DateTime::class => [
                'default_timezone' => Config::refer(App::class, 'timezone', date_default_timezone_get() ? : 'UTC'),
            ],

            //---------------------------------------------
            // Routing Configure
            //---------------------------------------------
            MethodRoute::class => [
                'namespace' => Config::refer(App::class, 'namespace.controller'),
            ],

            ControllerRoute::class => [
                'namespace' => Config::refer(App::class, 'namespace.controller'),
            ],

            ConventionalRoute::class => [
                'namespace' => Config::refer(App::class, 'namespace.controller'),
            ],

            //---------------------------------------------
            // View Engine Configure
            //---------------------------------------------
            Blade::class => Config::promise(function () {
                return [
                    'custom' => [
                        'directive' => [BladeCustom::directive()],
                        'if'        => [BladeCustom::if()],
                        'component' => [BladeCustom::component()],
                        'include'   => [BladeCustom::include()],
                    ],
                ];
            }),
        ]);
    }
    
    /**
     * Get application root path
     *
     * @return string
     */
    public static function getRoot() : string
    {
        return self::config('root');
    }

    /**
     * Set application root path by given path
     *
     * @param string $app_root_path
     */
    public static function setRoot(string $app_root_path) : void
    {
        self::setConfig(['root' => Files::normalizePath($app_root_path)]);
    }

    /**
     * Convert application root relative path to absolute path.
     *
     * @param $root_relative_path
     * @return string
     */
    public static function path(string $root_relative_path) : string
    {
        return Files::normalizePath(self::getRoot().'/'.$root_relative_path);
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
     * Set the current locale by given locale.
     *
     * @param string $locale
     */
    public static function setLocale(string $locale) : void
    {
        self::setConfig(['locale' => $locale]);
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
     * Get the current surface (inflow route/application invoke interface) like web, api, console.
     *
     * @return string
     */
    public static function getSurface() : string
    {
        return self::config('surface');
    }

    /**
     * Set the current surface (inflow route/application invoke interface) like web, api, console.
     *
     * @param string $surface
     */
    public static function setSurface(string $surface) : void
    {
        self::setConfig(['surface' => $surface]);
    }

    /**
     * Determine whether it is a specific surface (inflow route/application invoke interface) like web, api, console.
     *
     * @param string ...$surface
     */
    public static function surfaceIn(string ...$surface) : bool
    {
        return \in_array(self::getSurface(), $surface, true);
    }
    
    /**
     * It returns the value according to the environment based on the current execution environment.
     * The following can be specified for the key name of $case, and the value is acquired according to the priority of 1 => 4.
     *
     *  1. surface@env
     *  2. surface
     *  3. env
     *  4. default
     *
     * @param array $case
     * @return mixed
     */
    public static function when(array $case)
    {
        return Config::promise(function () use ($case) {
            $surface = App::getSurface();
            $env     = App::getEnv();
            return
                $case["{$surface}@{$env}"] ??
                $case[$surface] ??
                $case[$env] ??
                $case['default']
            ;
        }, false);
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
     * @param string $entry_point エントリポイント名
     */
    public static function setEntryPoint(string $entry_point) : void
    {
        self::setConfig(['entry_point' => $entry_point]);
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
