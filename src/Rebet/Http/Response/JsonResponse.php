<?php
namespace Rebet\Http\Response;

use Rebet\Http\Response;
use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;

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
     * Create Json Response
     *
     * @param mixed $data
     * @param integer $status
     * @param array $headers
     * @param integer $encoding_options
     */
    public function __construct($data = null, int $status = 200, array $headers = [], int $encoding_options = 0)
    {
        $this->encodingOptions = $encoding_options;
        parent::__construct($data, $status, $headers);
    }
}
