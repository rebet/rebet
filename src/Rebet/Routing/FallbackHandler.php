<?php
namespace Rebet\Routing;

use Rebet\Auth\Exception\AuthenticateException;
use Rebet\Http\Exception\FallbackException;
use Rebet\Http\Exception\HttpException;
use Rebet\Http\HttpStatus;
use Rebet\Http\ProblemRespondable;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Response;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Stream\Stream;
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
        $title  = Trans::get("message.fallbacks.{$status}.title") ?? $title ;
        $detail = Trans::get("message.fallbacks.{$status}.detail") ?? $detail ;

        $view = View::of("/fallbacks/{$status}");
        if ($view->exists()) {
            return Responder::toResponse($view->with([
                'status'    => $status,
                'title'     => $title ?? HttpStatus::reasonPhraseOf($status) ?? 'Unknown Error',
                'detail'    => $detail,
                'exception' => $e
            ]), $status);
        }

        return $this->makeDefaultView($status, $title, $detail, $request, $e);
    }

    /**
     * Create a default error page view response using given HTTP status code.
     *
     * @param integer $status
     * @param string|null $title
     * @param string|null $detail
     * @param Request $request
     * @param \Throwable $e
     * @return Response
     */
    protected function makeDefaultView(int $status, ?string $title, ?string $detail, Request $request, \Throwable $e) : Response
    {
        $custom_title = true;
        if ($title === null) {
            $title        = HttpStatus::reasonPhraseOf($status) ?? 'Unknown Error';
            $custom_title = false;
        }
        $view = View::of("/fallbacks/default");
        if ($view->exists()) {
            return Responder::toResponse($view->with([
                'status'    => $status,
                'title'     => $title,
                'detail'    => $detail,
                'exception' => $e
            ]), $status);
        }

        $home   = $request->getRoutePrefix().'/' ;
        $title  = Stream::of($title, true)->escape()->nl2br();
        if (!$custom_title) {
            $title = $title->text('<span class="status">'.$status.'</span>%s');
        }
        $detail = Stream::of($detail, true)->escape()->nl2br()->text('<div class="detail">%s</div>')->default('');
        $html   = <<<EOS
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css"> 
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
    <style type="text/css">
    <!--
    html {
        font-family: sans-serif;
    }
    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }
    .contents {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .title {
        font-weight: normal;
        text-align: center;
        color: #999;
        font-size: 3rem;
        margin: 10px;
        line-height: 1em;
    }
    .title .status {
        margin-right: 1rem;
    }
    .detail {
        color: #bbb;
        margin: 10px 20px 20px;
    }
    .action {
        text-align: center;
        margin: 10px;
        line-height: 1em;
    }
    .home {
        color: #999;
        font-size: 2.5rem;
    }
    .outline-outward {
        display: inline-block;
        position: relative;

        -webkit-tap-highlight-color: rgba(0,0,0,0);
        transform: translateZ(0);
        box-shadow: 0 0 1px rgba(0, 0, 0, 0);
        transition-duration: .3s;
    }
    .outline-outward:before {
        content: '';
        z-index: -1;
        position: absolute;
        border: #999 solid 3px;
        border-radius: 100%;
        top: -5px;
        right: -5px;
        bottom: -5px;
        left: -5px;
        transition-duration: .3s;
        transition-property: top right bottom left;
    }
    .outline-outward:hover {
        color: #666;
    }
    .outline-outward:hover:before {
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
    }
    @media screen and (max-width: 768px) {
        .title {
            font-size: 2.3rem;
        }
        .title .status {
            margin-right: 0px;
            display: block;
        }
    }
    -->
    </style>
</head>
<body>
    <div class="container">
        <div class="contents">
            <h2 class="title">{$title}</h2>
            {$detail}
            <div class="action">
                <a class="home outline-outward" href="{$home}"><i class="fas fa-arrow-alt-circle-left"></i></a>
            </div>
        </div>
    </div>
</body>
</html>
EOS
        ;

        return Responder::toResponse($html, $status, [], $request);
    }

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
