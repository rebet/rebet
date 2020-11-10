<?php
namespace Rebet\Validation;

/**
 * Validations Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Validations
{
    /**
     * Invoke validation the given name.
     *
     * @param string $name
     * @param Context $c
     * @param mixed ...$args
     * @return boolean
     */
    public function validate(string $name, Context $c, ...$args) : bool;
}
