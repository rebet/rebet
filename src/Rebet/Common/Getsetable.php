<?php
namespace Rebet\Common;

/**
 * Getsetable Trait
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait Getsetable
{
    /**
     * Support Get and Set interface using one method.
     *
     * @param string $property name
     * @param mixed $value
     * @return mixed
     */
    private function getset(string $property, $value)
    {
        if ($value === null) {
            return $this->{$property};
        }

        $this->{$property} = $value;
        return $this;
    }
}
