<?php
namespace Rebet\Routing;

use Rebet\Http\Exception\FallbackException;
use Rebet\Http\ProblemRespondable;
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
    public function handle(Request $request, \Throwable $e) : Response
    {
        return $request->expectsJson() ? $this->handleJson($request, $e) : $this->handleWeb($request, $e) ;
    }

    protected function handleJson(Request $request, \Throwable $e) : Response
    {
        switch (true) {
            case $e instanceof ProblemRespondable:
                return $this->report($request, $e->problem(), $e);
            default:
                break;
        }
    }

    protected function handleWeb(Request $request, \Throwable $e) : Response
    {
        switch (true) {
            case $e instanceof FallbackException:
                return $this->report($request, $e->redirect(), $e);
            default:
                break;
        }
    }

    protected function report(Request $request, Response $response, \Throwable $e) : Response
    {
        return $response;
    }
}
