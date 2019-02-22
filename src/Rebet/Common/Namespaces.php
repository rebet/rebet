<?php
namespace Rebet\Common;

use Rebet\Config\Configurable;

/**
 * Namespaces Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Namespaces
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'aliases' => [],
        ];
    }
    
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Set new alias.
     *
     * @param string $alias
     * @param string $actual can contaiins another alias.
     * @return void
     */
    public static function setAlias(string $alias, string $actual) : void
    {
        static::setConfig(['aliases' => [$alias => $actual]]);
    }

    /**
     * Resolve namespace alias that starts with '@' like '@controller\\UserController'.
     * Note: The leading '\' will be deleted.
     *
     * @param mixed $class
     * @return mixed
     */
    public static function resolve($class)
    {
        if ($class === null) {
            return null;
        }
        return is_string($class) ? static::_resolve($class) : $class ;
    }

    /**
     * Resolve namespace alias that starts with '@' recursively.
     *
     * @param string $class
     * @return string
     */
    private static function _resolve(string $class) : string
    {
        if (!Strings::startsWith($class, '@')) {
            return Strings::ltrim($class, '\\', 1);
        }
        $alias  = Strings::latrim($class, '\\');
        $actual = static::config("aliases.{$alias}");
        return static::_resolve(str_replace($alias, $actual, $class));
    }
}
