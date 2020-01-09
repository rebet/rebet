<?php
namespace Rebet\Database\Event;

use Rebet\Database\Database;

/**
 * Batch Deleting Event Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BatchDeleting implements Saving
{
    /**
     * @var Database
     */
    public $db;

    /**
     * The entity class name for deleting.
     *
     * @var string
     */
    public $entity;

    /**
     * Ransack conditions for deleting.
     *
     * @var mixed $ransacks conditions that arrayable
     */
    public $ransack;

    /**
     * Create an event
     *
     * @param Database $db
     * @param string $entity class name
     * @param mixed $ransacks conditions that arrayable
     */
    public function __construct(Database $db, string $entity, $ransack)
    {
        $this->db      = $db;
        $this->entity  = $entity;
        $this->ransack = $ransack;
    }
}
