<?php
namespace Rebet\Config;

use Rebet\Common\Arrays;
use Rebet\Common\DotAccessDelegator;
use Rebet\Common\Exception\LogicException;
use Rebet\Common\OverrideOption;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Common\Utils;
use Rebet\Config\Exception\ConfigNotDefineException;

/**
 * Config Class
 *
 * Classes that handle various settings in a unified way.
 * The configuration of this class has the following four layers of settings,
 *
 *  1. Library     Configuration (Low priority)
 *  2. Framework   Configuration
 *  3. Application Configuration
 *  4. Runtime     Configuration (High priority)
 *
 * You can overwrite the setting according by the higher priority layer.
 * In addition, each setting is defined / operated as follows.
 *
 *  1. Library Configuration
 *     => Implementation of Configurable Trait in each class definition
 *  2. Framework Configuration
 *     => Set / overwrite with Config::framework() in framework initialization processing
 *  3. Application Configuration
 *     => Set / overwrite with Config::application() in application initialization processing
 *  4. Runtime Configuration
 *     => Set / overwrite with Config::runtime() during application execution
 *     => The individual configration methods of the Configurable implementation class that using protected Configurable::setConfig() method.
 *
 * Note: The behaviors behave like Rebet\Common\Arrays::override($lower_layer, $higher_layer, $option, OverrideOption::PREPEND) in the above layer overwriting.
 *
 * @see Rebet\Config\Configurable
 * @see Rebet\Common\Arrays
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Config
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Configuration setting
     * The structure is as follows
     *
     * config = [
     *    'Layer' => [
     *       'Section' => [
     *           'key' => value,
     *       ],
     *    ],
     * ]
     *
     * @var array
     */
    protected static $config = [
        Layer::LIBRARY     => [],
        Layer::FRAMEWORK   => [],
        Layer::APPLICATION => [],
        Layer::RUNTIME     => [],
    ];

    /**
     * Option setting of config
     *
     * option = [
     *    'Layer' => [
     *       'Section' => [
     *           'key' => OverrideOption,
     *       ],
     *    ],
     * ]
     *
     * @var array
     */
    public static $option = [
        Layer::LIBRARY     => [],
        Layer::FRAMEWORK   => [],
        Layer::APPLICATION => [],
        Layer::RUNTIME     => [],
    ];

    /**
     * Compiled option setting
     *
     * compiled = [
     *   'Section' => [
     *       'key' => value,
     *   ],
     * ]
     *
     * @var array
     */
    protected static $compiled = [];

    /**
     * Clear configration data of given section.
     * If the null given then clear all data.
     *
     * @param string|null $section (default: null)
     * @param string ...$layers (default: all layers)
     * @return void
     */
    public static function clear(?string $section = null, string ...$layers) : void
    {
        $layers = empty($layers) ? [Layer::LIBRARY, Layer::FRAMEWORK, Layer::APPLICATION, Layer::RUNTIME] : $layers ;
        if ($section === null) {
            foreach ($layers as $layer) {
                static::$config[$layer] = [];
                static::$option[$layer] = [];
            }
            static::$compiled = [];
        } else {
            foreach ($layers as $layer) {
                unset(static::$config[$layer][$section], static::$option[$layer][$section]);
            }
            unset(static::$compiled[$section]);
        }
    }

    /**
     * Get all of configuration settings as dump string.
     *
     * Note:
     *  - Unused library configuration settings that have not yet been loaded are not included.
     *
     * @return string
     */
    public static function dump(string ...$sections) : string
    {
        static::all(); // Load the library configuration via ConfigReferrer by call all() method once.
        return Strings::toString(static::all(...$sections));
    }

    /**
     * Get all of configuration settings.
     *
     * Note:
     *  - Unused library configuration settings that have not yet been loaded are not included.
     *  - The library configuration setting loaded via ConfigReferrer by calling this method
     *    it may not be included in the return value of this method.
     *
     * @param string ...$sections (default: all)
     * @return array
     */
    public static function all(string ...$sections) : array
    {
        $target = empty($sections) ? static::$compiled : [] ;
        foreach ($sections as $section) {
            $target[$section] = static::$compiled[$section] ?? null ;
        }
        return Reflector::get($target, null, []);
    }

    /**
     * Compile the config setting of the target section.
     * This compilation is overwrite setting by Arrays::override(..., OverrideOption::PREPEND) of each layer information.
     *
     * @param string $section
     * @return void
     */
    protected static function compile(string $section) : void
    {
        $compiled = static::$config[Layer::LIBRARY][$section] ?? [];
        foreach ([Layer::FRAMEWORK, Layer::APPLICATION, Layer::RUNTIME] as $layer) {
            if (isset(static::$config[$layer][$section])) {
                $config   = static::$config[$layer][$section];
                $compiled = Arrays::override(
                    $compiled,
                    $config,
                    static::$option[$layer][$section] ?? [],
                    OverrideOption::PREPEND,
                    function ($base, $diff, $option, $default_array_override_option) {
                        if ($base instanceof DotAccessDelegator || $diff instanceof DotAccessDelegator) {
                            return static::promise(function () use ($base, $diff, $option, $default_array_override_option) {
                                $base = $base instanceof DotAccessDelegator ? $base->get() : $base ;
                                $diff = $diff instanceof DotAccessDelegator ? $diff->get() : $diff ;
                                return Arrays::override($base, $diff, $option, $default_array_override_option);
                            }, false);
                        }
                        return null;
                    }
                );
            }
        }

        static::$compiled[$section] = $compiled;
    }

    /**
     * Set / overwrite the configuration of the target layer.
     * This compilation is overwrite setting by Arrays::override(..., OverrideOption::PREPEND) of each layer information.
     *
     * @param string $layer
     * @param array $config
     * @return void
     */
    protected static function put(string $layer, array $config) : void
    {
        $config                 = self::analyze($config, static::$option[$layer]);
        static::$config[$layer] = Arrays::override(static::$config[$layer], $config, static::$option[$layer], OverrideOption::PREPEND);
        foreach (\array_keys($config) as $section) {
            static::loadLibraryConfig($section);
            static::compile($section);
        }
    }

    /**
     * Analyze configuration settings.
     *
     * @param mixed $config
     * @param array $option
     * @return void
     */
    protected static function analyze($config, array &$option)
    {
        if (!\is_array($config) || Arrays::isSequential($config)) {
            return $config;
        }
        
        $analyzed = [];
        foreach ($config as $section => $value) {
            if (\is_array($value) && !Arrays::isSequential($value)) {
                $option[$section] = $option[$section] ?? [] ;
                $value            = static::analyzeSection($value, $option[$section]);
            }
            
            $analyzed[$section] = $value;
        }
        return $analyzed;
    }

    /**
     * Analyze the configuration settings under the section.
     *
     * @param array $config
     * @param array $option
     * @return void
     */
    protected static function analyzeSection(array $config, array &$option)
    {
        if (!\is_array($config) || Arrays::isSequential($config)) {
            return $config;
        }

        $analyzed = [];
        foreach ($config as $key => $value) {
            [$key, $apply_option] = OverrideOption::split($key);
            if ($apply_option !== null) {
                $option[$key] = $apply_option;
            }
            
            if (\is_array($value) && !Arrays::isSequential($value)) {
                $nested_option = [];
                $value         = static::analyzeSection($value, $nested_option);
                if ($apply_option === null && !empty($nested_option)) {
                    $option[$key] = $nested_option ;
                }
            }

            $analyzed[$key] = $value;
        }
        return $analyzed;
    }

    /**
     * Set the framework layer config.
     * This compilation is overwrite setting by Arrays::override(..., OverrideOption::PREPEND) of each layer information.
     *
     * ex)
     * Config::framework([
     *     Dao::class => [
     *         'database' => 'rebet',
     *         'user' => 'rebet',
     *         'password' => 'password',
     *     ],
     *     DateTime::class => [
     *         'default_format' => 'Y/m/d H:i:s',
     *     ],
     *     'SectionName' => [
     *          'key' => 'value',
     *     ],
     * ]);
     */
    public static function framework(array $config) : void
    {
        self::put(Layer::FRAMEWORK, $config);
    }

    /**
     * Set the application layer config.
     * This compilation is overwrite setting by Arrays::override(..., OverrideOption::PREPEND) of each layer information.
     *
     * ex)
     * Config::application([
     *     Dao::class => [
     *         'database' => 'rebet',
     *         'user' => 'rebet',
     *         'password' => 'password',
     *     ],
     *     DateTime::class => [
     *         'default_format' => 'Y/m/d H:i:s',
     *     ],
     *     'SectionName' => [
     *          'key' => 'value',
     *     ],
     * ]);
     */
    public static function application(array $config) : void
    {
        self::put(Layer::APPLICATION, $config);
    }

    /**
     * Set the runtime layer config.
     * This compilation is overwrite setting by Arrays::override(..., OverrideOption::PREPEND) of each layer information.
     *
     * ex)
     * Config::runtime([
     *     Dao::class => [
     *         'database' => 'rebet',
     *         'user' => 'rebet',
     *         'password' => 'password',
     *     ],
     *     DateTime::class => [
     *         'default_format' => 'Y/m/d H:i:s',
     *     ],
     *     'SectionName' => [
     *          'key' => 'value',
     *     ],
     * ]);
     */
    public static function runtime(array $config) : void
    {
        self::put(Layer::RUNTIME, $config);
    }

    /**
     * It checks the configuration setting for the given target is defined.
     * Note: This method will throw an exception if the key selector contains only numeric values.
     *
     * @param array $config
     * @param string $section
     * @param string|null $key can contains dot notation
     * @return bool
     * @throws LogicException
     */
    protected static function isDefine(array $config, string $section, ?string $key) : bool
    {
        if (Utils::isBlank($key)) {
            return isset($config[$section]);
        }
        return isset($config[$section]) && Reflector::has($config[$section], $key) ;
    }

    /**
     * Check the format of the access key.
     *
     * @param string|null $key
     * @return void
     * @throws LogicException
     */
    protected static function validateKey(?string $key) : void
    {
        if (Utils::isBlank($key)) {
            return;
        }
        foreach (\explode('.', $key) as $value) {
            if (\ctype_digit($value)) {
                throw LogicException::by("Invalid config key access, the key '{$key}' contains digit only part.");
            }
        }
    }

    /**
     * Get the configuration value of given key.
     * If blank is given as the key name, all configuration settings will be acquired.
     *
     * Note:
     *  - This method will throw an exception if the key selector contains only numeric values.
     *  - When access with index specification is required, please access the target array individually after acquiring the data.
     *
     * @param string $section
     * @param string|null $key can contains dot notation (default: null)
     * @param bool $required (default: true) ... If this value is true then throw an exception when the configuration value is blank.
     * @param mixed $default (default: null)
     * @return mixed
     * @throws ConfigNotDefineException
     * @throws LogicException
     */
    public static function get(string $section, ?string $key = null, bool $required = true, $default = null)
    {
        static::validateKey($key);
        static::setup($section);
        $value = Reflector::get(static::$compiled[$section] ?? null, $key);
        if ($required && Utils::isBlank($value)) {
            throw ConfigNotDefineException::by("Required config {$section}".($key ? "#{$key}" : "")." is blank or not define.");
        }
        return $value ?? $default;
    }

    /**
     * Load library configuration from given section.
     *
     * @param string $section
     * @return void
     */
    protected static function loadLibraryConfig(string $section) : void
    {
        if (isset(static::$config[Layer::LIBRARY][$section])) {
            return;
        }
        static::$option[Layer::LIBRARY][$section] = [];
        static::$config[Layer::LIBRARY][$section] = method_exists($section, 'defaultConfig') ? static::analyze($section::defaultConfig(), static::$option[Layer::LIBRARY][$section]) : [] ;
    }

    /**
     * Create an instance from the configuration settings using Reflector::instantiate().
     *
     * @see Rebet\Common\Reflector::instantiate()
     *
     * @param string $section
     * @param string $key can contains dot notation
     * @param bool $required (default: true) ... If this value is true then throw an exception when the configuration value is blank.
     * @param mixed $default (default: null)
     * @return mixed
     * @throws ConfigNotDefineException
     * @throws LogicException
     */
    public static function instantiate(string $section, string $key, bool $required = true, $default = null)
    {
        return Reflector::instantiate(self::get($section, $key, $required, $default));
    }

    /**
     * It Checks the configuration of the config is defined.
     *
     * Note:
     *  - This method will throw an exception if the key selector contains only numeric values.
     *  - When access with index specification is required, please access the target array individually after acquiring the data.
     *
     * @param string $section
     * @param string $key can contains dot notation
     * @return bool
     * @throws LogicException
     */
    public static function has(string $section, string $key) : bool
    {
        static::validateKey($key);
        static::setup($section);
        return static::isDefine(static::$compiled, $section, $key);
    }

    /**
     * Setup the configuration settings for the given section
     *
     * @param string $section
     * @return void
     */
    protected static function setup(string $section) : void
    {
        if (!isset(static::$config[Layer::LIBRARY][$section])) {
            static::loadLibraryConfig($section);
            static::compile($section);
        }
        if (!isset(static::$compiled[$section])) {
            static::compile($section);
        }
    }

    /**
     * Returns the referrer that shares the configuration settings of other section / keys.
     *
     * ex)
     * public static function defaultConfig() {
     *     return [
     *         'default_format'   => 'Y-m-d H:i:s',
     *         'default_timezone' => Config::refer(Other::class, 'timezone', date_default_timezone_get() ?: 'UTC'),
     *     ];
     * }
     *
     * @param string $section to refer
     * @param string $key to refer can contains dot notation (default: null)
     * @param mixed $default when the referral configuration value is blank (default: null)
     * @return ConfigReferrer
     */
    public static function refer(string $section, ?string $key = null, $default = null) : ConfigReferrer
    {
        static::validateKey($key);
        return new ConfigReferrer($section, $key, $default);
    }

    /**
     * Returns a delay evaluation formula that delays the set value confirmation until its setting is referenced.
     *
     * ex)
     * // When config using getenv is loaded before DotEnv::load()
     * public static function defaultConfig() {
     *     return [
     *         'env' => Config::promise(function(){ return getenv('APP_ENV') ?: 'development' ; }),
     *     ];
     * }
     *
     * @param \Closure $promise
     * @param bool $only_once (default: true)
     * @return ConfigPromise
     */
    public static function promise(\Closure $promise, bool $only_once = true) : ConfigPromise
    {
        return new ConfigPromise($promise, $only_once);
    }
}
