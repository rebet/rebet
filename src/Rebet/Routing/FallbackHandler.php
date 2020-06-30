<?php
namespace Rebet\Routing;

use Rebet\Http\Request;
use Rebet\Http\Response;

/**
 * Fallback Handler Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class FallbackHandler
{
    /**
     * Must be able to instantiate without arguments.
     */
    abstract public function __construct();

    /**
     * Handle fallback page display.
     *
     * @param Request $request
     * @param \Throwable $e
     * @return Response
     */
    abstract public function fallback(Request $request, \Throwable $e) : Response;

    /**
     * Must be able to invoke as function.
     *
     * @param Request $request
     * @param \Throwable $e
     */
    public function __invoke($request, $e)
    {
        return $this->fallback($request, $e);
    }
}
