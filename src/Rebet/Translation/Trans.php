<?php
namespace Rebet\Translation;

use Rebet\Config\Config;
use Rebet\Config\Configurable;

/**
 * File Based Trans class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Trans
{
    use Configurable;

    public static function defaultConfig() : array
    {
        return [
            'translator' => Config::promise(function () {
                return new Translator(new FileLoader(Trans::config('resources.i18n')));
            }, false),
            'resources'  => [
                'i18n' => [],
            ],
        ];
    }

    /**
     * Translator
     *
     * @var Translator
     */
    protected static $translator = null;

    /**
     * Get the translator
     *
     * @return Translator
     */
    protected static function translator() : Translator
    {
        return static::$translator ?? static::$translator = static::config('translator') ;
    }

    /**
     * Get the grammar of given name.
     * This method get the value named "@{$name}" from translation resource.
     *
     * @param string $group
     * @param string $name
     * @param mixed $default (default: null)
     * @param string|null $locale (default: null)
     * @return mixed
     */
    public static function grammar(string $group, string $name, $default = null, string $locale = null)
    {
        return static::translator()->grammar($group, $name, $default, $locale);
    }

    /**
     * Get the translation for the given key.
     * If can not translate the given key then return the key without group.
     *
     * This translator normally recursive search for translated text by given nested key.
     * If this behavior is not desirable, you can suppress recursive search by adding '!' to the end of group name.
     *
     * @param string $key "{$group}.{$key}" or "{$group}!.{$key}"
     * @param array $replacement (default: [])
     * @param int|string|null $selector (default: null)
     * @param string $locale
     * @return string
     */
    public static function get(string $key, array $replace = [], $selector = null, ?string $locale = null) : ?string
    {
        return static::translator()->get($key, $replace, $selector, $locale);
    }

    /**
     * Get the ordinalize number for given locale.
     * If the ordinalize for given locale is nothing then return given number as it is.
     *
     * @param integer $num
     * @param string|null $locale (default: depend on self locale)
     * @return string
     */
    public static function ordinalize(int $num, ?string $locale = null) : string
    {
        return static::translator()->ordinalize($num, $locale);
    }
}
