<?php
namespace Rebet\Database\Event;

use Rebet\Database\Database;
use Rebet\Database\Entity;

/**
 * Updating Event Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Updating implements Saving
{
    /**
     * @var Database
     */
    public $db;

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
     * @param Database $db
     * @param Entity $old
     * @param Entity $new
     */
    public function __construct(Database $db, Entity $old, Entity &$new)
    {
        $this->db  = $db;
        $this->old = $old;
        $this->new = $new;
    }
}
