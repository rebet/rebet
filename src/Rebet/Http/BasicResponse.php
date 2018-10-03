<?php
namespace Rebet\Http;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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
     * Undocumented function
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
