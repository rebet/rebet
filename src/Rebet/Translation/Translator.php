<?php
namespace Rebet\Translation;

use Rebet\Config\Configurable;
use Rebet\Common\Reflector;
use Rebet\Common\Collection;

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
            'locale'          => null,
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
     * Create a new translator instance.
     *
     * @param Loader $loader
     * @param string|null $locale (default: depend on configure)
     * @param string|null $fallback_locale (default: depend on configure)
     */
    public function __construct(Loader $loader, ?string $locale = null, ?string $fallback_locale = null)
    {
        $this->loader          = $loader;
        $this->locale          = $locale          ?? static::config('locale') ;
        $this->fallback_locale = $fallback_locale ?? static::config('fallback_locale', false, 'en') ;
    }

    /**
     * Load the specified language group.
     *
     * @param string $locale
     * @param string $group
     * @return self
     */
    public function load(string $locale, string $group) : self
    {
        if ($this->isLoaded($locale, $group)) {
            return $this;
        }
        $this->resouces[$locale][$group] = $this->loader->load($locale, $group);
    }

    /**
     * Determine if the given group has been loaded.
     *
     * @param string $locale
     * @param string $group
     * @return boolean
     */
    public function isLoaded(string $locale, string $group) : bool
    {
        return isset($this->resouces[$locale][$group]);
    }

    /**
     * Get the translation for the given key.
     *
     * @param string $key
     * @param array $replace
     * @param string $locale
     * @return string
     */
    public function get(string $key, array $replace = [], ?string $locale = null) : string
    {
        [$group, $key] = explode('.', $key, 2);
        $locales = array_unique([$locale ?? $this->locale, $this->fallback_locale]);

        $line = null;
        foreach ($locales as $locale) {
            $this->load($locale, $group);
            $line = Reflector::get($this->resouces[$locale][$group], $key);
            if ($line) {
                break;
            }
        }

        if ($line === null) {
            throw new \LogicException("Not found [{$group}.{$key}] in ".join('/', $locales)." locale.");
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
