<?php
namespace Rebet\Database\Event;

use Rebet\Database\Entity;

/**
 * Deleting Event Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Deleting
{
    /**
     * The entity when before this event occured.
     *
     * @var Entity
     */
    public $old;

    /**
     * Create an event
     *
     * @param Entity $old
     */
    public function __construct(Entity $old)
    {
        $this->old = $old;
    }
}
