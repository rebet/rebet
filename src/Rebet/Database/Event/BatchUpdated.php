<?php
namespace Rebet\Database\Event;

use Rebet\Database\Database;
use Rebet\Tools\DateTime\DateTime;

/**
 * Batch Updated Event Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BatchUpdated implements Saving
{
    /**
     * @var Database
     */
    public $db;

    /**
     * The entity class name for updating.
     *
     * @var string
     */
    public $entity;

    /**
     * Column and value map for updated.
     *
     * @var array
     */
    public $sets;

    /**
     * Ransack conditions for updated.
     *
     * @var mixed $ransacks conditions that arrayable
     */
    public $ransack;

    /**
     * Now for updated.
     *
     * @var DateTime|null
     */
    public $now;

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
     * @param array $sets
     * @param mixed $ransacks conditions that arrayable
     * @param DateTime|null $now
     */
    public function __construct(Database $db, string $entity, array $sets, $ransack, ?DateTime $now, int $affected_rows)
    {
        $this->db            = $db;
        $this->entity        = $entity;
        $this->sets          = $sets;
        $this->ransack       = $ransack;
        $this->now           = $now;
        $this->affected_rows = $affected_rows;
    }
}
