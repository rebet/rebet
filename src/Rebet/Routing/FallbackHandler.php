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
    /**
     * Handle exception
     *
     * @param Request $request
     * @param \Throwable $e
     * @return Response
     */
    public function handle(Request $request, \Throwable $e) : Response
    {
        return $request->expectsJson() ? $this->handleJson($request, $e) : $this->handleView($request, $e) ;
    }

    /**
     * Handle exception when client expects Json.
     *
     * @param Request $request
     * @param \Throwable $e
     * @return ProblemResponse
     */
    protected function handleJson(Request $request, \Throwable $e) : Response
    {
        $response = null;
        switch (true) {
            case $e instanceof ProblemRespondable:
                $response = $e->problem();
                break;
            default:
                $response = $this->makeProblem(500, $request, $e);
                break;
        }
        $this->report($request, $response, $e);
        return $response;
    }

    /**
     * Create a ProblemResponse from given HTTP status code.
     * This method return the Problem Response (RFC7807 Problem Details for HTTP APIs).
     *
     * @param integer $status code of HTTP
     * @param Request $request
     * @param \Throwable $e
     * @return ProblemResponse
     */
    protected function makeProblem(int $status, Request $request, \Throwable $e) : ProblemResponse
    {
        return Responder::problem($status)->detail($e->getMessage());
    }

    /**
     * Handle exception when client do not expects Json.
     *
     * @param Request $request
     * @param \Throwable $e
     * @return Response
     */
    protected function handleView(Request $request, \Throwable $e) : Response
    {
        $response = null;
        switch (true) {
            case $e instanceof FallbackException:
                $response = $e->redirect();
                break;
            case $e instanceof HttpException:
                $response = $this->makeView($e->getStatus(), $e->getTitle(), $e->getDetail(), $request, $e);
                break;
            case $e instanceof AuthenticateException:
                $response = $this->makeView(403, null, $e->getMessage(), $request, $e);
                break;
            case $e instanceof RouteNotFoundException:
                $response = $this->makeView(404, null, $e->getMessage(), $request, $e);
                break;
            default:
                $response = $this->makeView(500, null, $e->getMessage(), $request, $e);
                break;
        }
        $this->report($request, $response, $e);
        return $response;
    }

    /**
     * Create a error page view response for given HTTP status code.
     *
     * @param integer $status
     * @param string|null $title
     * @param string|null $detail
     * @param Request $request
     * @param \Throwable $e
     * @return Response
     */
    protected function makeView(int $status, ?string $title, ?string $detail, Request $request, \Throwable $e) : Response
    {
        $title  = Trans::get("message.error_views.{$status}.title") ?? $title ?? HttpStatus::reasonPhraseOf($status) ?? 'Unknown Error' ;
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

        return $this->makeDefaultView($status, $title, $detail, $request, $e);
    }

    /**
     * Create a default error page view response using given HTTP status code.
     *
     * @param integer $status
     * @param string $title
     * @param string|null $detail
     * @param Request $request
     * @param \Throwable $e
     * @return Response
     */
    abstract protected function makeDefaultView(int $status, string $title, ?string $detail, Request $request, \Throwable $e) : Response ;

    /**
     * Report an exception to destination where you want to.
     *
     * @param Request $request
     * @param Response $response
     * @param \Throwable $e
     * @return void
     */
    abstract protected function report(Request $request, Response $response, \Throwable $e) : void ;

    /**
     * {@inheritDoc}
     */
    public function __invoke($request, $e)
    {
        return $this->handle($request, $e);
    }
}
