<?php
namespace Rebet\Translation;

use Rebet\Config\Configurable;
use Rebet\Common\Reflector;
use Rebet\Common\Collection;

/**
 * Translator Class
 *
 * @todo pluralization support
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
        $this->locale          = $locale          ?? static::config('default_locale') ;
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
     * Get the translation for the given key.
     *
     * @param string $key
     * @param array $replace
     * @param string $locale
     * @return string|null
     */
    public function get(string $key, array $replace = [], ?string $locale = null) : ?string
    {
        [$group, $key] = explode('.', $key, 2);
        $locales = array_unique([$locale ?? $this->locale, $this->fallback_locale]);

        $line = null;
        foreach ($locales as $locale) {
            $this->load($group, $locale);
            $line = Reflector::get($this->resouces[$group][$locale], $key);
            if ($line) {
                break;
            }
        }

        if ($line === null) {
            return null;
        }

        if (empty($replace)) {
            return $line;
        }

        $replace = Collection::valueOf($replace)->sortBy(function ($v, $k) {
            return mb_strlen($k) * -1;
        });

        foreach ($replace as $key => $value) {
            $line = str_replace(':'.$key, $value, $line);
        }

        return $line;
    }
}