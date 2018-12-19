<?php
namespace Rebet\Foundation\Routing;

use Rebet\Foundation\App;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Response;
use Rebet\Routing\FallbackHandler;
use Rebet\Stream\Stream;
use Rebet\Http\HttpStatus;
use Rebet\Log\Log;

/**
 * Basic Fallback Handler Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BasicFallbackHandler extends FallbackHandler
{
    /**
     * {@inheritDoc}
     */
    protected function makeDefaultView(int $status, string $title, ?string $detail, Request $request, \Throwable $e) : Response
    {
        $view = View::of("/errors/default");
        if ($view->exists()) {
            return Responder::toResponse($view->with([
                'status'    => $status,
                'title'     => $title,
                'detail'    => $detail,
                'exception' => $e
            ]), $status);
        }

        $home   = $request->getRoutePrefix().'/' ;
        $title  = Stream::of($title, true)->escape();
        $detail = Stream::of($detail, true)->escape()->nl2br()->text('<div class="detail">%s</div>');
        $html  = <<<EOS
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <style type="text/css">
    <!--
    -->
    </style>
</head>
<body>
    <div class="contents">
        <h2 class="title">{$status} {$title}</h2>
        {$detail}
        <a class="home" href="{$home}">HOME</a>
    </div>
</body>
</html>
EOS
        ;

        return Responder::toResponse($html, $status, [], $request);
    }

    /**
     * {@inheritDoc}
     */
    protected function report(Request $request, Response $response, \Throwable $e) : void
    {
        $status = $response->getStatusCode();
        switch (HttpStatus::classOf($status)) {
            case HttpStatus::INFORMATIONAL:
            case HttpStatus::SUCCESSFUL:
            case HttpStatus::REDIRECTION:
                // Do nothing
                return;

            case HttpStatus::CLIENT_ERROR:
                $reason = HttpStatus::reasonPhraseOf($status) ?? 'Unknown Client Error' ;
                Log::trace("HTTP {$status} {$reason} occurred.", [], $e);
                return;

            case HttpStatus::SERVER_ERROR:
                $reason = HttpStatus::reasonPhraseOf($status) ?? 'Unknown Server Error' ;
                Log::error("HTTP {$status} {$reason} occurred.", [], $e);
                return;

            default:
                $reason = HttpStatus::reasonPhraseOf($status) ?? 'Unknown Status Code' ;
                Log::error("HTTP {$status} {$reason} occurred.", [], $e);
                return;
        }
    }
}
