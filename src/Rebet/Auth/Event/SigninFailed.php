<?php
namespace Rebet\Auth\Event;

use Rebet\Http\Request;

/**
 * Signin Failed Event Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SigninFailed
{
    /**
     * The request when this event occured.
     *
     * @var Request
     */
    public $request;

    /**
     * Create an event
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
