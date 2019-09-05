<?php
namespace Rebet\Database\Event;

use Rebet\Database\Database;
use Rebet\Database\DataModel\Entity;

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
     * @var Database
     */
    public $db;

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
     * @param Entity $new
     */
    public function __construct(Database $db, Entity &$new)
    {
        $this->db  = $db;
        $this->new = $new;
    }
}
