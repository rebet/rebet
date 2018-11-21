<?php
namespace Rebet\Http\Response;

use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;
use Rebet\Http\Response;

/**
 * Json Response Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class JsonResponse extends SymfonyJsonResponse implements Response
{
    use Respondable;

    /**
     * JSON レスポンスを構築します。
     *
     * @param mixed $data
     * @param integer $status
     * @param array $headers
     * @param integer $encodingOptions
     */
    public function __construct($data = null, int $status = 200, array $headers = [], int $encodingOptions = 0)
    {
        $this->encodingOptions = $encodingOptions;
        parent::__construct($data, $status, $headers);
    }
}
