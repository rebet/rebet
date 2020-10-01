<?php
namespace Rebet\Http\Session\Storage\Handler;

use Rebet\Tools\Config\Configurable;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler as SymfonyPdoSessionHandler;

/**
 * Pdo Session Handler Class
 *
 * @todo Implements Configurable and default PDO connection.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class PdoSessionHandler extends SymfonyPdoSessionHandler
{
    // Currently, there is nothing to extends
}
