<?php
namespace Rebet\Http;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Response Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Response extends SymfonyResponse
{
    /**
     * Current response is the latest instantiated Response object.
     *
     * @var Response
     */
    protected static $current = null;

    /**
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     */
    public function __construct($content = '', int $status = 200, array $headers = [])
    {
        parent::__construct($content, $status, $headers);
        static::$current = $this;
    }

    /**
     * Get current response.
     *
     * @return Response
     */
    public static function current() : Response
    {
        return static::$current;
    }
}
