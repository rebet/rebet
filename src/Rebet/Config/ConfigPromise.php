<?php
namespace Rebet\Config;

use Rebet\Common\DotAccessDelegator;
use Rebet\Common\Callback;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;

/**
 * Config Promise Class
 *
 * It is used when you want to do lazy evaluation by putting it in config setting.
 * This object can be constructed using the Config::promise() facade.
 *
 * @see Rebet\Config\Config::promise()
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ConfigPromise implements DotAccessDelegator
{
    /**
     * @var \Closure of delay evaluation
     */
    private $promise = null;

    /**
     * Whether or not to determine the value by the first delay evaluation
     * @var bool
     */
    private $only_once = true;

    /**
     * Values ​​determined by delayed execution (used when only_once is true).
     * @var mixed
     */
    private $evaluated_value = null;

    /**
     * Whether it has already been evaluated (used when only_once is true).
     * @var mixed
     */
    private $is_evaluated = false;

    /**
     * Construct a delayed evaluation class.
     *
     * @param \Closure $promise of delay evaluation function():mixed
     * @param bool $only_once (default: true)
     */
    public function __construct(\Closure $promise, bool $only_once = true)
    {
        $this->promise   = $promise;
        $this->only_once = $only_once;
    }

    /**
     * Get the delay evaluation result.
     */
    public function get()
    {
        if (!$this->only_once) {
            return ($this->promise)();
        }
        if ($this->is_evaluated) {
            return $this->evaluated_value;
        }
        $this->evaluated_value = ($this->promise)();
        $this->is_evaluated    = true;
        return $this->evaluated_value;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        if($this->is_evaluated) {
            return Strings::toString($this->evaluated_value);
        }
        return "<Promise: ".($this->only_once ? "once" : "dynamic").'>';
    }
}
