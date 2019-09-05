<?php
namespace Rebet\Database\Event;

use Rebet\Database\Database;
use Rebet\Database\DataModel\Entity;

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
     * Create an event
     *
     * @param Database $db
     * @param Entity $old
     */
    public function __construct(Database $db, Entity $old)
    {
        $this->db  = $db;
        $this->old = $old;
    }
}
