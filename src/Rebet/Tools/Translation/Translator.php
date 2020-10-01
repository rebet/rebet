<?php
namespace Rebet\Tools\Translation;

use Rebet\Tools\Arrays;
use Rebet\Tools\Callback;
use Rebet\Tools\Strings;
use Rebet\Tools\Tinker;
use Rebet\Tools\Config\Configurable;

/**
 * Translator Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Translator
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'dictionary'      => FileDictionary::class,
            'resource_adder'  => [
                FileDictionary::class => function (FileDictionary $dictionary, ...$args) { $dictionary->addLibraryResource(...$args); },
            ],
            'locale'          => null,
            'fallback_locale' => 'en',
            'ordinalize'      => [
                'en' => function (int $num) {
                    return in_array($num % 100, [11, 12, 13]) ? $num.'th' : $num.(['th', 'st', 'nd', 'rd'][$num % 10] ?? 'th');
                },
            ],
        ];
    }

    /**
     * Dictionary
     *
     * @var Dictionary
     */
    protected static $dictionary = null;

    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Add resource to the dictionary if will use the specified dictionary class.
     *
     * @param string $class of dictionary
     * @param mixed ...$args of dictionary resource parameters
     * @return void
     */
    public static function addResourceTo(string $class, ...$args) : void
    {
        $dictionary = static::dictionary();
        if ($dictionary instanceof $class) {
            $resource_adder = static::config('resource_adder.'.$class, false);
            if ($resource_adder) {
                call_user_func($resource_adder, $dictionary, ...$args);
            }
        }
    }

    /**
     * Get the dictionary.
     *
     * @return Dictionary
     */
    public static function dictionary() : Dictionary
    {
        if (static::$dictionary === null) {
            static::$dictionary = static::configInstantiate('dictionary');
        }
        return static::$dictionary;
    }

    /**
     * Get the locale.
     *
     * @return string|null
     */
    public static function getLocale() : ?string
    {
        return static::config('locale', false);
    }

    /**
     * Set locale by given locale
     *
     * @param string $locale (default: null)
     * @param string|null $fallback_locale if null given then do nothing (default: null)
     * @return string|null
     */
    public static function setLocale(string $locale, ?string $fallback_locale = null) : void
    {
        static::setConfig(['locale' => $locale]);
        if ($fallback_locale !== null) {
            self::setConfig(['fallback_locale' => $fallback_locale]);
        }
    }

    /**
     * Get the fallback locale.
     *
     * @return string
     */
    public static function getFallbackLocale() : string
    {
        return static::config('fallback_locale');
    }

    /**
     * Clear the given language group resouces.
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$dictionary = null;
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
        return static::dictionary()->grammar($group, $name, $locale ?? static::config('locale'), $default);
    }

    /**
     * Get the translation for the given key.
     * If can not translate the given key then return null.
     *
     * This translator normally recursive search for translated text by given nested key, like below.
     *
     *  1) First, try to get 'custom.nested.key'
     *  2) If not found then try to get 'nested.key'
     *  3) If not found then try to get 'key'
     *  4) If still can not find it then return null
     *
     * If this behavior is not desirable, you can suppress recursive search by $recursive option.
     *
     * @param string|null $key "{$group}.{$key}"
     * @param array $replacement (default: [])
     * @param int|string|null $selector (default: null)
     * @param bool $recursive (default: true)
     * @param string $locale (default: depend on configure)
     * @return string|null
     */
    public static function get(?string $key, array $replacement = [], $selector = null, bool $recursive = true, ?string $locale = null) : ?string
    {
        if ($key === null) {
            return null;
        }
        [$group, $key] = Strings::split($key, '.', 2);
        if ($key === null) {
            return null;
        }

        $locale   = $locale ?? static::config('locale');
        $sentence = static::dictionary()->sentence($group, $key, array_unique([$locale, static::config('fallback_locale')]), $selector, $recursive);

        return static::replace($sentence, $replacement, static::grammar($group, 'delimiter', ', ', $locale));
    }

    /**
     * Replace the placeholder in translation text by given replacement.
     *
     * @param string|null $sentence
     * @param array $replacement
     * @param string $delimiter for join array to string (default: ', ')
     * @return string|null
     */
    public static function replace(?string $sentence, array $replacement, string $delimiter = ', ') : ?string
    {
        if ($sentence === null) {
            return null;
        }

        if (empty($replacement)) {
            return $sentence;
        }

        $replacement = Tinker::with($replacement, true)->sortKeys(SORT_DESC, Callback::compareLength())->return();
        foreach ($replacement as $key => $value) {
            $sentence = str_replace(':'.$key, Arrays::implode($value, $delimiter) ?? $value, $sentence);
        }

        return $sentence;
    }

    /**
     * Set the ordinalize callback for given locale.
     *
     * @param string $locale
     * @param callable $ordinalize function($number):mixed
     * @return void
     */
    public static function setOrdinalize(string $locale, callable $ordinalize) : void
    {
        static::setConfig(['ordinalize' => [$locale => \Closure::fromCallable($ordinalize)]]);
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
        $locale     = $locale ?? static::config('locale');
        $ordinalize = static::config("ordinalize.{$locale}", false, function (int $num) { return $num; });
        return (string)$ordinalize($num);
    }
}
