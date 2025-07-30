<?php
namespace Rebet\Application;

use Rebet\Auth\Auth;
use Rebet\Event\Event;
use Rebet\Filesystem\Storage;
use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Request;
use Rebet\Http\Session\Session;
use Rebet\Log\Log;
use Rebet\Mail\Mail;
use Rebet\Routing\Router;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Config\ConfigPromise;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Enum\Enum;
use Rebet\Tools\Template\Letterpress;
use Rebet\Tools\Testable\System;
use Rebet\Tools\Translation\Translator;
use Rebet\Tools\Utility\Env;
use Rebet\View\Engine\Twig\Node\EmbedNode;
use Rebet\View\View;

/**
 * Application Config Class
 *
 * Define and manage application configuration settings.
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

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/src/Rebet/Application/Console/Command/skeltons/configs/application.letterpress.php
     */
    public static function defaultConfig()
    {
        return [
            'domain'          => 'localhost',
            'locale'          => locale_get_default(),
            'fallback_locale' => 'en',
            'timezone'        => date_default_timezone_get() ?: 'UTC',
            'paginate'        => [
                'page_name'        => 'page',
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
        return static::$kernel;
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
     * Initialize App and set configure.
     *
     * @param Kernel $kernel
     * @return Kernel
     */
    public static function init(Kernel $kernel) : Kernel
    {
        static::$kernel = $kernel;
        $kernel->bootstrap();
        return $kernel;
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
     * Get the application code name.
     *
     * @return string
     */
    public static function codeName() : string
    {
        return self::config('code_name');
    }

    /**
     * Get the application domain.
     *
     * @return string
     */
    public static function domain() : string
    {
        return self::config('domain');
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
     * If the 'APP_ENV' environment value undefined then return 'development'.
     *
     * @return string
     */
    public static function env() : string
    {
        return Env::get('APP_ENV', 'development');
    }

    /**
     * Determine whether it is a specific environment.
     *
     * @param string ...$env
     */
    public static function envIn(string ...$env) : bool
    {
        return \in_array(self::env(), $env, true);
    }

    /**
     * Get the current channel (inflow route/application invoke interface) like web, api, console.
     *
     * @return string|null
     */
    public static function channel() : ?string
    {
        return static::$kernel ? static::$kernel->channel() : null ;
    }

    /**
     * Determine whether it is a specific channel (inflow route/application invoke interface) like web, api, console.
     *
     * @param string ...$channel
     */
    public static function channelIn(string ...$channel) : bool
    {
        return \in_array(self::channel(), $channel, true);
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
            $channel = App::channel();
            $env     = App::env();
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

    /**
     * Clear the application state.
     *
     * @return void
     */
    public static function clear() : void
    {
        Config::clear();
        System::clear();
        Enum::clear();
        Event::clear();
        Cookie::clear();
        Request::clear();
        Session::clear();
        Router::clear();
        Translator::clear();
        EmbedNode::clear();
        View::clear();
        Storage::clean();
        Letterpress::clear();
        Log::clear();
        Mail::clear();
        Auth::clear();
    }
}
