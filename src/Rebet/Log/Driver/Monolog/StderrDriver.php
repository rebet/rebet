<?php
namespace Rebet\Log\Driver\Monolog;

use Monolog\Handler\StreamHandler;
use Rebet\Log\Driver\Monolog\Formatter\TextFormatter;

/**
 * Stderr Driver Class
 *
 * This class based on Monolog\Handler\StreamHandler for php://stderr.
 *
 * Usage: (Parameter of Constractor)
 *     'name'            [*] string of name (usualy same as channel name),
 *     'level'           [*] string of LogLevel::*,
 *     'format'          [ ] string of format template (default: null for use TextFormat class config)
 *     'stringifiers'    [ ] placeholder stringify setting of format template (default: [] for use TextFormat class config)
 *     'bubble'          [ ] boolean of bubble (default: true)
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class StderrDriver extends MonologDriver
{
    /**
     * Create Stderr ouput logging driver.
     *
     * @param string $name
     * @param string $level
     * @param string|null $format (default: null)
     * @param array $stringifiers (default: [])
     * @param boolean $bubble (default: true)
     */
    public function __construct(string $name, string $level, string $format = null, array $stringifiers = [], bool $bubble = true)
    {
        $handler = new StreamHandler(defined('STDERR') ? STDERR : 'php://stderr', $level, $bubble, null, false);
        $handler->setFormatter(new TextFormatter($format, $stringifiers));
        parent::__construct($name, $level, [$handler]);
    }
}
