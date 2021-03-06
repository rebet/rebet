<?php
namespace Rebet\Http\Response;

use Rebet\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse as SymfonyStreamedResponse;

/**
 * Streamed Response Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class StreamedResponse extends SymfonyStreamedResponse implements Response
{
    use Respondable;

    /**
     * Create Streamed Response
     *
     * @param callable $callback function():void { streamd send content logic }
     * @param integer $status (default: 200)
     * @param array $headers (default: [])
     */
    public function __construct(callable $callback = null, int $status = 200, array $headers = [])
    {
        parent::__construct($callback, $status, $headers);
    }
}
