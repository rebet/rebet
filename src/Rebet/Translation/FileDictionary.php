<?php
namespace Rebet\Translation;

use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Config\Config;
use Rebet\Config\Configurable;
use Rebet\Config\Layer;
use Rebet\Config\LocaleResource;

/**
 * File Dictionary Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class FileDictionary implements Dictionary
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'resources' => [
                'i18n' => static::$resouce_dirs,
            ],
        ];
    }

    /**
     * @var array of resouce adders class names
     */
    protected static $resouce_adders = [];

    /**
     * @var array of resouce dirs
     */
    protected static $resouce_dirs = [];

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
    public $resouces = [];

    /**
     * Resource file suffix
     *
     * @var string
     */
    protected $suffix = null;

    /**
     * Resource file loading option
     *
     * @see Rebet\Config\Resource
     * @var array
     */
    protected $option = [];

    /**
     * Create a new file dictionary instance.
     *
     * @param string $suffix (default: 'php')
     * @param array $option (default: [])
     */
    public function __construct(string $suffix = 'php', array $option = [])
    {
        $this->suffix = $suffix;
        $this->option = $option;
    }

    /**
     * Add the given group resouces to Library layer resouces.
     *
     * @param string $resource files directory path
     * @param string ...$group
     * @return self
     */
    public function addLibraryResource(string $resource, string ...$groups) : self
    {
        if (in_array($resource, static::$resouce_dirs, true)) {
            return $this;
        }

        static::$resouce_dirs[] = $resource;
        static::clearConfig(Layer::LIBRARY);

        if (empty($groups)) {
            $this->clear();
            return $this;
        }

        foreach ($groups as $group) {
            $this->clear($group);
        }
        return $this;
    }

    /**
     * Clear the given language group resouces.
     *
     * @param string|null $group (default: null)
     * @param string|null $locale (default: null)
     * @return self
     */
    public function clear(?string $group = null, ?string $locale = null) : self
    {
        if ($group !== null && $locale !== null) {
            unset($this->resouces[$group][$locale]);
            return $this;
        }
        if ($group !== null) {
            unset($this->resouces[$group]);
            return $this;
        }
        $this->resouces = [];
        return $this;
    }

    /**
     * Load the given language group.
     *
     * @param string $group
     * @param string $locale
     * @return self
     */
    protected function load(string $group, string $locale) : self
    {
        if ($this->isLoaded($group, $locale)) {
            return $this;
        }
        $this->resouces[$group][$locale] = LocaleResource::load(array_reverse(static::config('resources.i18n', false, [])), $locale, $group, $this->suffix, $this->option);
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
     * {@inheritDoc}
     */
    public function grammar(string $group, string $key, string $locale, $default = null)
    {
        $this->load($group, $locale);
        return Reflector::get($this->resouces[$group][$locale], "@{$key}", $default);
    }

    /**
     * {@inheritDoc}
     */
    public function sentence(string $group, string $key, array $locales, $selector = null, bool $recursive = true) : ?string
    {
        $sentence = null;
        foreach ($locales as $locale) {
            $this->load($group, $locale);
            $sentence = Reflector::get($this->resouces[$group][$locale], $key);
            if ($sentence) {
                break;
            }
        }

        $sentence = $this->choose($sentence, $selector);
        if ($sentence !== null) {
            return $sentence;
        }

        if ($recursive && Strings::contains($key, '.')) {
            return $this->sentence($group, Strings::lbtrim($key, '.'), $locales, $selector, $recursive);
        }

        return null ;
    }

    /**
     * Select a proper translation string based on the given selector.
     *
     * @param string|array $sentence
     * @param int|string|null $selector
     * @return string|null
     */
    protected function choose($sentence, $selector) : ?string
    {
        if (is_null($sentence)) {
            return null;
        }
        if (is_null($selector) && is_string($sentence) && !Strings::contains($sentence, '|')) {
            return $sentence;
        }
        $segments = is_array($sentence) ? $sentence : explode('|', $sentence) ;
        $sentence = null;
        foreach ($segments as $part) {
            if (! is_null($sentence = $this->extract($part, $selector))) {
                break;
            }
        }
        if (is_null($sentence)) {
            return null;
        }
        return trim($sentence) ;
    }

    /**
     * Get the translation string if the condition matches.
     *
     * @param string $part
     * @param int|string $number
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
                [$from, $to] = Strings::split($condition, ',', 2);
                if ($selector === null) {
                    return $from === '*' && $to === '*' ? $value : null ;
                }
                if ($to === '*' && $selector >= $from) {
                    return $value;
                }
                if ($from === '*' && $selector <= $to) {
                    return $value;
                }
                if ($selector >= $from && $selector <= $to) {
                    return $value;
                }
            } elseif ($condition == $selector || $condition === '*') {
                return $value;
            }
            return null;
        }
        return $condition === '*' || in_array($selector, explode(',', $condition)) ? $value : null;
    }
}
