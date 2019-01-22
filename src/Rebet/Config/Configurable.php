<?php
namespace Rebet\Config;

use Rebet\Common\Arrays;

/**
 * Configurable Trait
 *
 * By implementing this trait, config can be used in the target class in the following form.
 *
 *   self::config('key');  // or static::config('key');
 *
 * Also, you can access the configuration settings from the outside as follows.
 *
 *   ConfigurableImplements::config('key');
 *
 * The above access is synonymous with the following code.
 *
 *   Config::get(ConfigurableImplements::class, 'key');
 *
 * Therefore, the default configuration settings implemented by this trait can be overwritten as follows.
 *
 *   Config::application([
 *       ConfigurableImplement::class => [
 *           'key' => 'new value'
 *       ]
 *   ]);
 *
 * @see Rebet\Config\Config
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait Configurable
{
    /**
     * Default config settings.
     * Define default settings of the library in each trait implemantion class.
     * Since the settings returned here are automatically classified into sections of the trait implementation class name,
     * Section specification is unnecessary.
     *
     * ex)
     * // Examples of definitions in classes related to databases
     * public static function defaultConfig() {
     *     return [
     *         'driver'   => 'mysql',
     *         'host'     => 'localhost',
     *         'port'     => 3306,
     *         'database' => null,
     *         'user'     => null,
     *     ];
     * }
     *
     * // Examples of definitions in classes related to date and time
     * public static function defaultConfig() {
     *     return [
     *         'default_format'   => 'Y-m-d H:i:s',
     *         'default_timezone' => Config::refer(Other::class, 'timezone', date_default_timezone_get() ?: 'UTC'),
     *     ];
     * }
     *
     * // Inherit a class that implements Configurable and overwrite new setting in subclass
     * public static function defaultConfig() {
     *     return static::parentConfigOverride([
     *         'default_format' => 'M d, Y g:i A',
     *         'new_key'        => 'new_value',
     *     ];
     * }
     * 
     * @return array|string
     */
    abstract public static function defaultConfig() ;

    /**
     * Differentially override the default config setting of the parent class.
     *
     * Note:
     * Please be aware that it is the copy of the setting in the initial state that is inherited.
     * It means the setting change content of the parent class is not followed.
     * (The settings between subclass and parent class is completely different)
     *
     * @param array $config of diff
     * @return array
     */
    protected static function parentConfigOverride(array $diff) : array
    {
        $rc   = new \ReflectionClass(static::class);
        $base = $rc->getParentClass()->getMethod('defaultConfig')->invoke(null);
        return Arrays::override($base, $diff);
    }

    /**
     * Get the own configuration setting.
     * If blank is given as the key name, all configuration settings will be acquired.
     *
     * @param string|null $key can contains dot notation (default: null)
     * @param bool $required (default: true) ... If this value is true then throw an exception when the configuration value is blank.
     * @param mixed $default (default: null)
     * @return mixed
     * @throws ConfigNotDefineException
     */
    public static function config(?string $key = null, bool $required = true, $default = null)
    {
        return Config::get(static::class, $key, $required, $default);
    }

    /**
     * Create an instance from the own configuration settings using Reflector::instantiate().
     *
     * @see Rebet\Config\Config::instantiate()
     * @see Rebet\Common\Reflector::instantiate()
     *
     * @param string $key can contains dot notation
     * @param bool $required (default: true) ... If this value is true then throw an exception when the configuration value is blank.
     * @param mixed $default (default: null)
     * @return mixed
     * @throws ConfigNotDefineException
     */
    protected static function configInstantiate(string $key, bool $required = true, $default = null)
    {
        return Config::instantiate(static::class, $key, $required, $default);
    }

    /**
     * Update own configuration settings by given config.
     * This method adds the configuration setting of the runtime layer.
     *
     * @param array $config
     */
    protected static function setConfig(array $config) : void
    {
        Config::runtime([static::class => $config]);
    }

    /**
     * It clears own configuration setting.
     *
     * @param string ...$layers (default: all layers)
     */
    protected static function clearConfig(string ...$layers) : void
    {
        Config::clear(static::class, ...$layers);
    }
}
