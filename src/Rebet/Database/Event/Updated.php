<?php
namespace Rebet\Database\Event;

use Rebet\Database\Entity;

/**
 * Updated Event Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Updated implements Saved
{
    /**
     * The entity when before this event occured.
     *
     * @var Entity
     */
    public $old;

    /**
     * The entity when this event occured.
     *
     * @var Entity
     */
    public $new;

    /**
     * Create an event
     *
     * @param Entity $old
     * @param Entity $new
     */
    public function __construct(Entity $old, Entity &$new)
    {
        $this->old = $old;
        $this->new = $new;
    }
}
