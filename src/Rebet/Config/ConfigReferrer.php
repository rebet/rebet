<?php
namespace Rebet\Config;

use Rebet\Common\DotAccessDelegator;
use Rebet\Common\Strings;

/**
 * Config Referrer Class
 *
 * It is a class to use when sharing configuration settings of other sections.
 * Note:
 *  - The reference will be unidirectional reference.
 *  - This object can be constructed using Config::refer() facade.
 *
 * @see Rebet\Config\Config::refer()
 *
 * @todo Circular reference detection & exception throw
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ConfigReferrer implements DotAccessDelegator
{
    /**
     * @var string of refer section name
     */
    private $section = null;

    /**
     * @var string of refer key name
     */
    private $key = null;

    /**
     * @var mixed default value when reference destination is blank
     */
    private $default = null;

    /**
     * Create a Config Referrer Class
     *
     * @param string $section name of refer section (or class)
     * @param string $key name of refer key can contains dot notation
     * @param mixed $default (default: null)
     * @return mixed
     */
    public function __construct(string $section, $key, $default = null)
    {
        $this->section = $section;
        $this->key     = $key;
        $this->default = $default;
    }

    /**
     * Gets the current set value of the reference destination.
     *
     * @return mixed
     */
    public function get()
    {
        return Config::get($this->section, $this->key, false, $this->default);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return "<Referrer: {$this->section}.{$this->key} (default: ".Strings::toString($this->default).")>";
    }
}
