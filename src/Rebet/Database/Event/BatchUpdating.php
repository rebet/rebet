<?php
declare(strict_types=1);

namespace Rebet\Database\Event;

use Rebet\Database\Database;
use Rebet\Tools\DateTime\DateTime;

/**
 * Batch Updating Event Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BatchUpdating implements Saving
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
     * Column and value map for updating.
     *
     * @var array<string, mixed>
     */
    public $sets;

    /**
     * Ransack conditions for updating.
     *
     * @var mixed $ransack conditions that arrayable
     */
    public $ransack;

    /**
     * Now for updating.
     *
     * @var DateTime|null
     */
    public $now;

    /**
     * Create an event
     *
     * @param Database $db
     * @param string $entity class name
     * @param array<string, mixed> $sets
     * @param mixed $ransack conditions that arrayable
     * @param DateTime|null $now
     */
    public function __construct(Database $db, string $entity, array $sets, $ransack, DateTime|null $now)
    {
        $this->db      = $db;
        $this->entity  = $entity;
        $this->sets    = $sets;
        $this->ransack = $ransack;
        $this->now     = $now;
    }
}
