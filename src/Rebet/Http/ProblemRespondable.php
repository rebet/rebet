<?php


namespace Rebet\Http;

use Rebet\Http\Response\ProblemResponse;

/**
 * Problem Respondable (RFC7807 Problem Details for HTTP APIs) Interface
 *
 * @see https://tools.ietf.org/html/rfc7807
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface ProblemRespondable
{
    /**
     * Create a Problem Response
     *
     * @return ProblemResponse
     */
    public function problem() : ProblemResponse ;
}
