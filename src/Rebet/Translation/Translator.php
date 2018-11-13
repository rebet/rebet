<?php
namespace Rebet\Translation;

use Rebet\Common\Collection;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Config\Configurable;

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
            'default_locale'  => null,
            'fallback_locale' => 'en',
            'ordinalize'      => [
                'en' => function (int $num) {
                    return in_array($num % 100, [11, 12, 13]) ? $num.'th' : $num.(['th', 'st', 'nd', 'rd'][$num % 10] ?? 'th');
                },
            ]
        ];
    }
    
    /**
     * Resource Loader
     *
     * @var Loader
     */
    protected $loader = null;

    /**
     * Loaded Resource Data
     *
     * $resouces = [
     *     'group' => [
     *         'locale' => [
     *             'key' => 'messsage',
     *         ],
     *     ]
     * ]
     *
     * @var array
     */
    protected $resouces = [];

    /**
     * The current locale
     *
     * @var string
     */
    protected $locale;

    /**
     * The fallback locale
     *
     * @var string
     */
    protected $fallback_locale;

    /**
     * Set default locale by given locale
     *
     * @param string $locale
     * @return void
     */
    public static function setDefaultLocale(string $locale) : void
    {
        static::setConfig(['default_locale' => $locale]);
    }
    
    /**
     * Create a new translator instance.
     *
     * @param Loader $loader
     * @param string|null $locale (default: depend on configure)
     * @param string|null $fallback_locale (default: depend on configure)
     */
    public function __construct(Loader $loader, ?string $locale = null, ?string $fallback_locale = null)
    {
        $this->loader          = $loader;
        $this->locale          = $locale ?? static::config('default_locale') ;
        $this->fallback_locale = $fallback_locale ?? static::config('fallback_locale', false, 'en') ;
    }

    /**
     * Load the specified language group.
     *
     * @param string $group
     * @param string $locale
     * @return self
     */
    public function load(string $group, string $locale) : self
    {
        if ($this->isLoaded($group, $locale)) {
            return $this;
        }
        $this->resouces[$group][$locale] = $this->loader->load($group, $locale);
        return $this;
    }

    /**
     * Determine if the given group has been loaded.
     *
     * @param string $group
     * @param string $locale
     * @return boolean
     */
    public function isLoaded(string $group, string $locale) : bool
    {
        return isset($this->resouces[$group][$locale]);
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
    public function grammar(string $group, string $name, $default = null, string $locale = null)
    {
        $locale = $locale ?? $this->locale;
        $this->load($group, $locale);
        return Reflector::get($this->resouces[$group][$locale], "@{$name}", false, $default);
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
    public function get(string $key, array $replacement = [], $selector = null, ?string $locale = null) : string
    {
        [$group, $key]    = explode('.', $key, 2);
        $recursive_search = true;
        if (Strings::endsWith($group, '!')) {
            $recursive_search = false;
            $group            = Strings::rtrim($group, '!');
        }
        $trans_locales = array_unique([$locale ?? $this->locale, $this->fallback_locale]);

        $line = null;
        foreach ($trans_locales as $trans_locale) {
            $this->load($group, $trans_locale);
            $line = Reflector::get($this->resouces[$group][$trans_locale], $key);
            if ($line) {
                break;
            }
        }
        $line = $this->choose($line, $selector);
        if ($line === null) {
            if ($recursive_search && Strings::contains($key, '.')) {
                $parent_key = Strings::lbtrim($key, '.');
                $line       = $this->get("{$group}.{$parent_key}", $replacement, $selector, $locale);
                return $line === $parent_key ? $key : $line ;
            }
            return $key ;
        }

        return $this->replace($group, $line, $replacement, $locale);
    }

    /**
     * Replace the placeholder in translation text by given replacement.
     *
     * @param string $group
     * @param string|null $line
     * @param array $replacement (default: [])
     * @param string|null $locale (default: null)
     * @return string|null
     */
    public function replace(string $group, ?string $line, array $replacement = [], ?string $locale = null) : ?string
    {
        if ($line === null) {
            return null;
        }

        if (empty($replacement)) {
            return $line;
        }

        $replacement = Collection::valueOf($replacement)->sortBy(function ($v, $k) { return mb_strlen($k) * -1; });
        $delimiter   = $this->grammar($group, 'delimiter', ', ', $locale);
        foreach ($replacement as $key => $value) {
            $value = is_array($value) ? implode($delimiter, $value) : $value ;
            $line  = str_replace(':'.$key, $value, $line);
        }

        return $line;
    }

    /**
     * Put the message for the given key and locale.
     *
     * @param string $key
     * @param string $message
     * @param string $locale (default: null)
     * @return self
     */
    public function put(string $key, string $message, ?string $locale = null) : self
    {
        [$group, $key] = explode('.', $key, 2);
        $locale        = $locale ?? $this->locale;
        $this->load($group, $locale);
        $this->resouces[$group][$locale][$key] = $message;
        return $this;
    }

    /**
     * Set the ordinalize callback for given locale.
     *
     * @param string $locale
     * @param callable $ordinalize
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
    public function ordinalize(int $num, ?string $locale = null) : string
    {
        $locale     = $locale ?? $this->locale;
        $ordinalize = static::config("ordinalize.{$locale}", false, function (int $num) {
            return $num;
        });
        return (string)$ordinalize($num);
    }

    /**
     * Select a proper translation string based on the given selector.
     *
     * @param  string|array  $line
     * @param  int|string|null  $selector
     * @return string|null
     * @throws LogicException
     */
    protected function choose($line, $selector) : ?string
    {
        if (is_null($line)) {
            return null;
        }
        if (is_null($selector) && is_string($line)) {
            return $line;
        }
        $segments = is_array($line) ? $line : explode('|', $line) ;
        $line     = null;
        foreach ($segments as $part) {
            if (! is_null($line = static::extract($part, $selector))) {
                break;
            }
        }
        if (is_null($line)) {
            return null;
        }
        return trim($line) ;
    }

    /**
     * Get the translation string if the condition matches.
     *
     * @param  string  $part
     * @param  int|string  $number
     * @return string|null
     */
    protected function extract(string $part, $selector) : ?string
    {
        preg_match('/^[\{\[]([^\[\]\{\}]*)[\}\]](.*)/s', $part, $matches);
        if (count($matches) !== 3) {
            return $part;
        }
        $condition = $matches[1];
        $value     = $matches[2];
        if (Strings::startsWith($part, '[')) {
            if (Strings::contains($condition, ',')) {
                [$from, $to] = array_pad(explode(',', $condition, 2), 2, null);
                if ($to === null && $selector == $from) {
                    return $value;
                } elseif ($to === '*' && $selector >= $from) {
                    return $value;
                } elseif ($from === '*' && $selector <= $to) {
                    return $value;
                } elseif ($selector >= $from && $selector <= $to) {
                    return $value;
                }
            } elseif ($condition == $selector) {
                return $value;
            }
            return null;
        }
        return $condition === '*' || in_array($selector, explode(',', $condition)) ? $value : null;
    }

    /**
     * Get the locale.
     *
     * @return string
     */
    public function getLocale() : string
    {
        return $this->locale;
    }

    /**
     * Get the fallback locale.
     *
     * @return string
     */
    public function getFallbackLocale() : string
    {
        return $this->fallback_locale;
    }
}
