<?php
namespace Rebet\Routing;

use Rebet\Http\Request;
use Rebet\Http\Response;


/**
 * Route interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Route
{
    public function isMatch(Request $request) : bool ;

    public function handle(Request $request) : Response ;

    public function shutdown() : void ;
}
