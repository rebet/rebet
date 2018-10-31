<?php
namespace Rebet\Validation;

use Rebet\Config\Configurable;
use Rebet\File\Files;
use Rebet\Config\Config;
use Rebet\Config\LocaleResource;
use Rebet\Translation\Translator;
use Rebet\Translation\FileLoader;
use Rebet\Common\Collection;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Common\Arrays;
use Rebet\Common\Utils;
use Rebet\Common\System;
use Rebet\Config\Resource;
use Rebet\DateTime\DateTime;

/**
 * Abstract Validations Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class Validations
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'customs' => [],
        ];
    }

    /**
     * Add custom validation to validations.
     *
     * @param string $name
     * @param \Closure $validation
     * @return void
     */
    public static function register(string $name, \Closure $validation) : void
    {
        static::setConfig(['customs' => [$name => $validation]]);
    }

    /**
     * Get the default translator for this validations.
     *
     * @return Translator
     */
    abstract public function translator() : Translator ;
    
    /**
     * Invoke validation the given name.
     * If registered custom validation is exists then invoke it first.
     *
     * @param string $name
     * @param Context $c
     * @param mixed ...$args
     * @return boolean
     */
    public function validate(string $name, Context $c, ...$args) : bool
    {
        $custom = static::config("customs.{$name}", false, null);
        return $custom ? $custom($c, ...$args) : Reflector::invoke($this, "validation{$name}", array_merge([$c], $args));
    }
}
