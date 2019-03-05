<?php
namespace Rebet\Foundation\Routing;

use Rebet\Common\Strings;
use Rebet\Http\HttpStatus;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Log\Log;
use Rebet\Routing\FallbackHandler;

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
                Log::trace("HTTP {$status} {$reason} occurred.\n".$this->requestToString($request), [], $e);
                return;

            case HttpStatus::SERVER_ERROR:
                $reason = HttpStatus::reasonPhraseOf($status) ?? 'Unknown Server Error' ;
                Log::error("HTTP {$status} {$reason} occurred.\n".$this->requestToString($request), [], $e);
                return;

            default:
                $reason = HttpStatus::reasonPhraseOf($status) ?? 'Unknown Status Code' ;
                Log::error("HTTP {$status} {$reason} occurred.\n".$this->requestToString($request), [], $e);
                return;
        }
    }

    /**
     * Create a request string for logging.
     *
     * @param Request $request
     * @return string
     */
    protected function requestToString(Request $request) : string
    {
        return Strings::indent("----- [HTTP REQUEST] -----\n{$request}\n--------------------------", '=> ');
    }
}
