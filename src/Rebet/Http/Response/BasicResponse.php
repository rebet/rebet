<?php
namespace Rebet\Http\Response;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Rebet\Http\Response;

/**
 * Basic Response Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BasicResponse extends SymfonyResponse implements Response
{
    use Respondable;

    /**
     * Create a Response
     *
     * @param string $content
     * @param integer $status
     * @param array $headers
     */
    public function __construct($content = '', int $status = 200, array $headers = [])
    {
        parent::__construct($content, $status, $headers);
    }
}