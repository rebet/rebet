<?php
namespace Rebet\Foundation\Console;

use Rebet\Console\Application;
use Rebet\Foundation\Console\Command\InitCommand;

/**
 * Assistant Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Assistant extends Application
{
    /**
     * Create Rebet assistant console application.
     */
    public function __construct()
    {
        parent::__construct();
        $this->add(new InitCommand());
    }
}
