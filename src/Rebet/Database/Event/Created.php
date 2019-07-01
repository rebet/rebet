<?php
namespace Rebet\Database\Event;

use Rebet\Database\Entity;

/**
 * Created Event Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Created implements Saved
{
    /**
     * The entity when this event occured.
     *
     * @var Entity
     */
    public $new;

    /**
     * Create an event
     *
     * @param Entity $new
     */
    public function __construct(Entity &$new)
    {
        $this->new = $new;
    }
}