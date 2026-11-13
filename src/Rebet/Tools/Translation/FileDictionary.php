<?php

declare(strict_types=1);

namespace Rebet\Tools\Translation;

use Override;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Config\Layer;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Resource\LocaleResource;
use Rebet\Tools\Utility\Strings;

/**
 * File Dictionary Class
 *
 * NOTE: The lookup result of the key is memorized (cached) until the resource is cleared by clear() (or addLibraryResource()).
 *       So that the DotAccessDelegator (e.g. Config::promise()) in the resource is evaluated only once at the first lookup,
 *       and the evaluated value is used after that even if the delegator returns the other value for each evaluation.
 *       Please call clear() if you need to evaluate it again.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class FileDictionary implements Dictionary
{
    use Configurable;

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/skeltons/app/config/tools.lp.php
     */
    #[Override]
    public static function defaultConfig()
    {
        return [
            'resources' => [
                'i18n' => static::$resouce_dirs,
            ],
        ];
    }

    /**
     * @var array<int, class-string> of resouce adders class names
     */
    protected static $resouce_adders = [];

    /**
     * @var array<int, string> of resouce dirs
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
     * @var array<string, array<string, array<string, string>>>
     */
    public $resouces = [];

    /**
     * The memorized lookup results of the key (including the not found result as null).
     *
     * $lookups = [
     *     'group' => [
     *         'locale' => [
     *             'key' => 'value or null',
     *         ],
     *     ]
     * ]
     *
     * NOTE: The DotAccessDelegator in the resource is evaluated at the first lookup, and the evaluated value is memorized.
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    protected $lookups = [];


    /**
     * The parsed choices of the sentence. (the key is the sentence, and the value is the list of the parsed parts)
     *
     * @var array<string, array<int, array{0: string, 1: string|null, 2: string|null, 3: string|null, 4: string, 5: array<int, string>}>>
     */
    protected $choices = [];

    /**
     * Resource file suffix
     *
     * @var string
     */
    protected $suffix;

    /**
     * Resource file loading option
     *
     * @see Rebet\Tools\Resource\Resource
     * @var array<string, mixed>
     */
    protected $option = [];

    /**
     * Create a new file dictionary instance.
     *
     * @param string               $suffix (default: 'php')
     * @param array<string, mixed> $option (default: [])
     */
    public function __construct(string $suffix = 'php', array $option = [])
    {
        $this->suffix = $suffix;
        $this->option = $option;
    }

    /**
     * Add the given group resouces to Library layer resouces.
     *
     * @param  string $resource  files directory path
     * @param  string ...$groups
     * @return self
     */
    public function addLibraryResource(string $resource, string ...$groups): self
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
     * @param  string|null $group  (default: null)
     * @param  string|null $locale (default: null)
     * @return self
     */
    public function clear(string|null $group = null, string|null $locale = null): self
    {
        if ($group !== null && $locale !== null) {
            unset($this->resouces[$group][$locale], $this->lookups[$group][$locale]);
            return $this;
        }
        if ($group !== null) {
            unset($this->resouces[$group], $this->lookups[$group]);
            return $this;
        }
        $this->resouces = [];
        $this->lookups  = [];
        $this->choices  = [];
        return $this;
    }

    /**
     * Load the given language group.
     *
     * @param  string $group
     * @param  string $locale
     * @return self
     */
    protected function load(string $group, string $locale): self
    {
        if ($this->isLoaded($group, $locale)) {
            return $this;
        }
        $this->resouces[$group][$locale] = LocaleResource::load(array_reverse(static::config('resources.i18n', false, [])), $locale, $group, $this->suffix, $this->option);
        unset($this->lookups[$group][$locale]);
        return $this;
    }

    /**
     * Look up the value of given key from the loaded resource. (the DotAccessDelegator is resolved)
     * The result is memorized until the resource is cleared. (the DotAccessDelegator is evaluated only once)
     *
     * @param  string $group
     * @param  string $locale
     * @param  string $key
     * @return mixed  null if not found
     */
    protected function lookup(string $group, string $locale, string $key)
    {
        $this->load($group, $locale);
        if (isset($this->lookups[$group][$locale]) && array_key_exists($key, $this->lookups[$group][$locale])) {
            return $this->lookups[$group][$locale][$key];
        }
        return $this->lookups[$group][$locale][$key] = Reflector::get($this->resouces[$group][$locale], $key);
    }

    /**
     * Determine if the given group has been loaded.
     *
     * @param  string  $group
     * @param  string  $locale
     * @return boolean
     */
    public function isLoaded(string $group, string $locale): bool
    {
        return isset($this->resouces[$group][$locale]);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function grammar(string $group, string $key, string $locale, $default = null)
    {
        return $this->lookup($group, $locale, "@{$key}") ?? $default;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<int, string> $locales
     */
    #[Override]
    public function sentence(string $group, string $key, array $locales, $selector = null, bool $recursive = true): string|null
    {
        $sentence = null;
        foreach ($locales as $locale) {
            $sentence = $this->lookup($group, $locale, $key);
            // NOTE: The empty string (and empty array) is treated as not translated, but the falsy string '0' is a valid translation.
            if ($sentence !== null && $sentence !== '' && $sentence !== []) {
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
     * @param  string|array<int|string, string>|null $sentence
     * @param  int|string|null                       $selector
     * @return string|null
     */
    protected function choose($sentence, $selector): string|null
    {
        if (is_null($sentence)) {
            return null;
        }
        if (is_null($selector) && is_string($sentence) && !Strings::contains($sentence, '|')) {
            return $sentence;
        }
        $choices  = $this->parseChoices($sentence);
        $sentence = null;
        foreach ($choices as $choice) {
            if (! is_null($sentence = $this->match($choice, $selector))) {
                break;
            }
        }
        if (is_null($sentence)) {
            return null;
        }
        return trim($sentence) ;
    }

    /**
     * Parse the given sentence into the list of the choices. (the result is cached for each sentence)
     *
     * The parsed choice is [$part, $bracket, $condition, $value, $from, $to] (or [$part, $bracket, $condition, $value, '', $words] for '{...}').
     * The $bracket is null when the part has no condition.
     *
     * @param  string|array<int|string, string>                                                                               $sentence
     * @return array<int, array{0: string, 1: string|null, 2: string|null, 3: string|null, 4: string, 5: array<int, string>}>
     */
    protected function parseChoices($sentence): array
    {
        $cache_key = is_array($sentence) ? "\x00" . implode("\x00", $sentence) : $sentence;
        if (isset($this->choices[$cache_key])) {
            return $this->choices[$cache_key];
        }
        $choices = [];
        foreach (is_array($sentence) ? $sentence : explode('|', $sentence) as $part) {
            $choices[] = $this->parseChoice($part);
        }
        return $this->choices[$cache_key] = $choices;
    }

    /**
     * Parse the given part of the sentence.
     *
     * @param  string                                                                                             $part
     * @return array{0: string, 1: string|null, 2: string|null, 3: string|null, 4: string, 5: array<int, string>}
     */
    protected function parseChoice(string $part): array
    {
        preg_match('/^[\{\[]([^\[\]\{\}]*)[\}\]](.*)/s', $part, $matches);
        if (count($matches) !== 3) {
            return [$part, null, null, null, '', []];
        }
        $condition = $matches[1];
        $value     = $matches[2];
        if ($part[0] === '[') {
            [$from, $to] = Strings::contains($condition, ',') ? Strings::split($condition, ',', 2) : [null, null];
            return [$part, '[', $condition, $value, (string) $from, $to === null ? [] : [$to]];
        }
        return [$part, '{', $condition, $value, '', explode(',', $condition)];
    }

    /**
     * Get the translation string of the parsed choice if the condition matches.
     *
     * @param  array{0: string, 1: string|null, 2: string|null, 3: string|null, 4: string, 5: array<int, string>} $choice
     * @param  int|string|null                                                                                    $selector
     * @return string|null
     */
    protected function match(array $choice, $selector): string|null
    {
        [$part, $bracket, $condition, $value, $from, $extra] = $choice;
        if ($bracket === null) {
            return $part;
        }
        if ($bracket === '[') {
            if (!empty($extra)) {
                $to = $extra[0];
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
        return $condition === '*' || in_array($selector, $extra, true) ? $value : null;
    }

    /**
     * Get the translation string if the condition matches.
     *
     * @param  string          $part
     * @param  int|string|null $selector
     * @return string|null
     */
    protected function extract(string $part, $selector): string|null
    {
        return $this->match($this->parseChoice($part), $selector);
    }
}
