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
class SigninFailed implements Authentication
{
    /**
     * The request when this event occured.
     *
     * @var Request
     */
    public $request;

    /**
     * Charenged Sign-in ID when sign-in failed.
     *
     * @var mixed
     */
    public $charenged_signin_id;

    /**
     * Create an event
     *
     * @param Request $request
     * @param mixed $charenged_signin_id when sign-in failed. (default: null)
     */
    public function __construct(Request $request, $charenged_signin_id = null)
    {
        $this->request             = $request;
        $this->charenged_signin_id = $charenged_signin_id;
    }
}
