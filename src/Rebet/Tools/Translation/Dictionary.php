<?php
namespace Rebet\Tools\Translation;

/**
 * Dictionary Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Dictionary
{
    /**
     * Get the grammar of given key.
     * This method get the value named "@{$key}" from translation dictionary resource.
     *
     * @param string $group
     * @param string $key
     * @param string $locale
     * @param mixed $default (default: null)
     * @return mixed
     */
    public function grammar(string $group, string $key, string $locale, $default = null) ;

    /**
     * Get the translation sentence for the given group/key.
     * If can not translate the given group/key then return null.
     *
     * This dictionary normally recursive search for translated text by given nested key.
     * If this behavior is not desirable, you can suppress recursive search by $recursive option.
     *
     * @param string $group
     * @param string $key can contains dot notation
     * @param array $locales
     * @param int|string|null $selector (default: null)
     * @param bool $recursive (default: true)
     * @return string|null
     */
    public function sentence(string $group, string $key, array $locales, $selector = null, bool $recursive = true) : ?string ;
}
