<?php
namespace Rebet\Translation;

/**
 * Loader Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Loader
{
    /**
     * Load the messages for the given locale.
     *
     * @param string $group
     * @param string $locale
     * @return array
     */
    public function load(string $group, string $locale) : array;
}
