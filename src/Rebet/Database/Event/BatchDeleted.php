<?php
namespace Rebet\Database\Event;

use Rebet\Database\Database;

/**
 * Batch Deleted Event Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BatchDeleted implements Saving
{
    /**
     * @var Database
     */
    public $db;

    /**
     * The entity class name for deleted.
     *
     * @var string
     */
    public $entity;

    /**
     * Ransack conditions for deleted.
     *
     * @var mixed $ransacks conditions that arrayable
     */
    public $ransack;

    /**
     * Affected rows count
     *
     * @var int
     */
    public $affected_rows;

    /**
     * Create an event
     *
     * @param Database $db
     * @param string $entity class name
     * @param mixed $ransacks conditions that arrayable
     */
    public function __construct(Database $db, string $entity, $ransack, int $affected_rows)
    {
        $this->db            = $db;
        $this->entity        = $entity;
        $this->ransack       = $ransack;
        $this->affected_rows = $affected_rows;
    }
}
