<?php
namespace Rebet\Application\Error;

use Rebet\Auth\Exception\AuthenticateException;
use Rebet\Tools\Tinker;
use Rebet\Filesystem\Exception\FileNotFoundException;
use Rebet\Http\Exception\FallbackRedirectException;
use Rebet\Http\Exception\HttpException;
use Rebet\Http\Exception\TokenMismatchException;
use Rebet\Http\HttpStatus;
use Rebet\Http\ProblemRespondable;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Response;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Log\Log;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Routing\FallbackHandler;
use Rebet\Translation\Translator;
use Rebet\View\View;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Exception Handler Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ExceptionHandler extends FallbackHandler
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
    }

    /**
     * Report an exception.
     * Just only report, this function do not display result.
     *
     * @param Request|InputInterface $input
     * @param Response|int|null $result
     * @param \Throwable $e
     * @return void
     */
    public function report($input, $result, \Throwable $e) : void
    {
        $input instanceof Request ? $this->reportHttp($input, $result, $e) : $this->reportConsole($input, $result, $e);
    }

    /**
     * Report an exception to destination where you want to.
     *
     * @param Request $request
     * @param Response|null $response
     * @param \Throwable $e
     * @return void
     */
    protected function reportHttp(Request $request, ?Response $response, \Throwable $e) : void
    {
        if (!$response) {
            Log::warning("Unhandled exception occurred.", compact('request'), $e);
            return;
        }

        $status = $response->getStatusCode();
        switch (HttpStatus::classOf($status)) {
            case HttpStatus::INFORMATIONAL:
            case HttpStatus::SUCCESSFUL:
            case HttpStatus::REDIRECTION:
                // Do nothing
                return;

            case HttpStatus::CLIENT_ERROR:
                $reason = HttpStatus::reasonPhraseOf($status) ?? 'Unknown Client Error' ;
                Log::debug("HTTP {$status} {$reason} occurred.", compact('request'), $e);
                return;

            case HttpStatus::SERVER_ERROR:
                $reason = HttpStatus::reasonPhraseOf($status) ?? 'Unknown Server Error' ;
                Log::error("HTTP {$status} {$reason} occurred.", compact('request'), $e);
                return;

            default:
                $reason = HttpStatus::reasonPhraseOf($status) ?? 'Unknown Status Code' ;
                Log::error("HTTP {$status} {$reason} occurred.", compact('request'), $e);
                return;
        }
    }

    /**
     * Report console channel exception.
     *
     * @param InputInterface $input
     * @param int|null $status
     * @param \Throwable $e
     * @return void
     */
    protected function reportConsole(InputInterface $input, ?int $status, \Throwable $e) : void
    {
        Log::error("Console unhandled exception occurred. Error code: {$status}", ['arguments' => $input->getArguments(), 'options' => $input->getOptions()], $e);
    }

    /**
     * Handle an exception
     *
     * @param Request|InputInterface $input
     * @param null|OutputInterface $output
     * @param \Throwable $e
     * @return Response|int
     */
    public function handle($input, $output, \Throwable $e)
    {
        return $input instanceof Request ? $this->handleHttp($input, $e) : $this->handleConsole($input ?? new ArgvInput(), $output ?? new ConsoleOutput(), $e);
    }

    /**
     * Handle http(web and api) channel exception.
     *
     * @param Request $request
     * @param \Throwable $e
     * @return Response
     */
    protected function handleHttp(Request $request, \Throwable $e) : Response
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
        $this->reportHttp($request, $response, $e);
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
        return Responder::problem($status)->detail(Translator::get("message.http.{$status}.detail") ?? $e->getMessage());
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
            case $e instanceof FallbackRedirectException:
                $response = $e->redirect();
                break;
            case $e instanceof HttpException:
                $reason_phrase = HttpStatus::reasonPhraseOf($e->getStatus());
                $response      = $this->makeView($e->getStatus(), $reason_phrase === $e->getTitle() ? null : $e->getTitle(), $e->getDetail(), $request, $e);
                break;
            case $e instanceof AuthenticateException:
                $response = $this->makeView(403, null, $e->getMessage(), $request, $e);
                break;
            case $e instanceof RouteNotFoundException: // Do not break.
            case $e instanceof TokenMismatchException: // Do not break.
            case $e instanceof FileNotFoundException:
                $response = $this->makeView(404, null, $e->getMessage(), $request, $e);
                break;
            default:
                $response = $this->makeView(500, null, $e->getMessage(), $request, $e);
                break;
        }
        $this->reportHttp($request, $response, $e);
        return $response;
    }

    /**
     * Create a error page view response for given HTTP status code.
     *
     * If the view of "/errors/{$status}" is exists, then will be used it.
     * Otherwise, will be created default view by makeDefaultView().
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
        $title  = Translator::get("message.http.{$status}.title") ?? $title ;
        $detail = Translator::get("message.http.{$status}.detail") ?? $detail ;

        if (View::isEnabled()) {
            $view = View::of("/errors/{$status}");
            if ($view->exists()) {
                return Responder::toResponse($view->with([
                    'status'    => $status,
                    'title'     => $title ?? HttpStatus::reasonPhraseOf($status) ?? 'Unknown Error',
                    'detail'    => $detail,
                    'exception' => $e
                ]), $status);
            }
        }

        return $this->makeDefaultView($status, $title, $detail, $request, $e);
    }

    /**
     * Create a default error page view response using given HTTP status code.
     *
     * If the view of "/errors/default" is exists, then will be used it.
     * Otherwise, will be created framework default view.
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

        if (View::isEnabled()) {
            $view = View::of("/errors/default");
            if ($view->exists()) {
                return Responder::toResponse($view->with([
                    'status'    => $status,
                    'title'     => $title,
                    'detail'    => $detail,
                    'exception' => $e
                ]), $status);
            }
        }

        $home   = $request->getRoutePrefix().'/' ;
        $title  = Tinker::with($title, true)->escape()->nl2br();
        if (!$custom_title) {
            $title = $title->stringf('<span class="status">'.$status.'</span>%s');
        }
        $detail = Tinker::with($detail, true)->escape()->nl2br()->stringf('<div class="detail">%s</div>')->default('');
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
     * Handle console channel exception.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param \Throwable $e
     * @return integer
     */
    protected function handleConsole(InputInterface $input, OutputInterface $output, \Throwable $e) : int
    {
        $status = 1;
        $this->reportConsole($input, $status, $e);
        $output->writeln('<error>********************************************</error>');
        $output->writeln('<error>*   Console Unhandled Exception Occurred   *</error>');
        $output->writeln('<error>********************************************</error>');
        $output->writeln('<comment>'.$e->getMessage().'</comment>');
        $output->writeln('Exception:');
        $output->writeln($e->getTraceAsString());
        return $status;
    }

    /**
     * {@inheritDoc}
     */
    public function fallback(Request $request, \Throwable $e) : Response
    {
        return $this->handle($request, null, $e);
    }
}
