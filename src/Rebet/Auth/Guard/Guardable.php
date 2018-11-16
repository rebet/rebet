<?php
namespace Rebet\Auth\Guard;

/**
 * Guardable Trait
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait Guardable
{
    /**
     * Authenticator name of this guard.
     *
     * @var string
     */
    protected $authenticator = null;

    /**
     * Get and Set authenticator name of this guard.
     *
     * @param string|null $name
     * @return mixed
     */
    public function authenticator(?string $name = null)
    {
        return $name === null ? $this->authenticator : $this->authenticator = $name ;
    }
}
