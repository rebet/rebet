<?php
namespace Rebet\Database\Driver;

/**
 * PDO Driver Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class PdoDriver extends \PDO implements Driver
{
    /**
     * Create database driver based on PDO.
     *
     * @param string $dsn
     * @param string|null $user (default: null)
     * @param string|null $password (default: null)
     * @param array|null $options (default: null)
     */
    public function __construct(string $dsn, ?string $user = null, ?string $password = null, ?array $options = null)
    {
        parent::__construct($dsn, $user, $password, $options);
    }
}
