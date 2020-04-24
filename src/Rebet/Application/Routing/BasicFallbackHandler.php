<?php
namespace Rebet\Application\Routing;

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
}
