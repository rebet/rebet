<?php
namespace Rebet\Tools\Reflection;

/**
 * Dot Access Delegator Interface
 *
 * It is an interface that permits access passing through transparently to the delegate destination object
 * by the "dot" notation access by Reflector::get().
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface DotAccessDelegator
{
    /**
     * Get the delegate destination object.
     *
     * @return mixed
     */
    public function get();
}
