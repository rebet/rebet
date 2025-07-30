<?php
namespace Rebet\Log\Driver;

use Psr\Log\LoggerInterface as PsrLogger;

/**
 * Nameable Driver Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface NameableDriver extends PsrLogger
{
    /**
     * Set a name to this driver.
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name) : self;

    /**
     * Get a name of this driver.
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Return a new cloned instance with the name changed
     *
     * @param string $name
     * @return PsrLogger
     */
    public function withName(string $name): PsrLogger;
}
