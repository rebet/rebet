<?php
namespace Rebet\Routing;

use Rebet\Http\Request;

/**
 * Controller class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class Controller
{
    /**
     * Request
     *
     * @var Request
     */
    public $request = null;

    /**
     * Route
     *
     * @var Route
     */
    public $route = null;
}
