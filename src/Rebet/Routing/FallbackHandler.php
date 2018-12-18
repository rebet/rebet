<?php
namespace Rebet\Routing;

use Rebet\Auth\Exception\AuthenticateException;
use Rebet\Http\Exception\FallbackException;
use Rebet\Http\Exception\HttpException;
use Rebet\Http\Http;
use Rebet\Http\ProblemRespondable;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Response;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Translation\Trans;
use Rebet\View\View;

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
        return $request->expectsJson() ? $this->handleJson($request, $e) : $this->handleView($request, $e) ;
    }

    protected function handleJson(Request $request, \Throwable $e) : ProblemResponse
    {
        $response = null;
        switch (true) {
            case $e instanceof ProblemRespondable:
                $response = $e->problem();
                break;
            default:
                $response = $this->errorJson(500, $request, $e);
                break;
        }
        $this->report($request, $response, $e);
        return $response;
    }

    protected function errorJson(int $status, Request $request, \Throwable $e) : ProblemResponse
    {
        return Responder::problem($status)->detail($e->getMessage());
    }

    protected function handleView(Request $request, \Throwable $e) : Response
    {
        $response = null;
        switch (true) {
            case $e instanceof FallbackException:
                $response = $e->redirect();
                break;
            case $e instanceof HttpException:
                $response = $this->errorView($e->getStatus(), $e->getTitle(), $e->getDetail(), $request, $e);
                break;
            case $e instanceof AuthenticateException:
                $response = $this->errorView(403, null, $e->getMessage(), $request, $e);
                break;
            case $e instanceof RouteNotFoundException:
                $response = $this->errorView(404, null, $e->getMessage(), $request, $e);
                break;
            default:
                $response = $this->errorView(500, null, $e->getMessage(), $request, $e);
                break;
        }
        $this->report($request, $response, $e);
        return $response;
    }

    protected function errorView(int $status, ?string $title, ?string $detail, Request $request, \Throwable $e) : Response
    {
        $title  = Trans::get("message.error_views.{$status}.title") ?? $title ?? Http::labelOf($status) ?? 'Unknown Error' ;
        $detail = Trans::get("message.error_views.{$status}.detail") ?? $detail ;
        $param  = [
            'status'    => $status,
            'title'     => $title,
            'detail'    => $detail,
            'exception' => $e
        ];

        $view = View::of("/errors/{$status}");
        if ($view->exists()) {
            return Responder::toResponse($view->with($param), $status);
        }

        $view = View::of("/errors/default");
        if ($view->exists()) {
            return Responder::toResponse($view->with($param), $status);
        }

        $html = <<<EOS
EOS
        ;
        return Responder::toResponse($html, $status);
    }

    abstract protected function report(Request $request, Response $response, \Throwable $e) : void ;

    /**
     * {@inheritDoc}
     */
    public function __invoke($request, $e)
    {
        return $this->handle($request, $e);
    }
}
