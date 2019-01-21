<?php
namespace Rebet\Translation;

use Rebet\Config\LocaleResource;

/**
 * Loader Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class FileLoader implements Loader
{
    /**
     * Resource loading path
     *
     * @var array
     */
    private $loading_path = [];

    /**
     * Resource file suffix
     *
     * @var string
     */
    private $suffix = null;

    /**
     * Resource file loading option
     *
     * @see Rebet\Config\Resource
     * @var array
     */
    private $option = [];

    /**
     * Create a new file loader instance.
     *
     * @param string|array $loading_path
     * @param string $suffix (default: .php)
     * @param array $option (default: [])
     */
    public function __construct($loading_path, string $suffix = 'php', array $option = [])
    {
        $this->loading_path = (array)$loading_path;
        $this->suffix       = $suffix;
        $this->option       = $option;
    }

    /**
     * Load the messages for the given locale.
     *
     * @param string $group
     * @param string $locale
     * @return array
     */
    public function load(string $group, string $locale) : array
    {
        return LocaleResource::load($this->loading_path, $locale, $group, $this->suffix, $this->option);
    }
}
