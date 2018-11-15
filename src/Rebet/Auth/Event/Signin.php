<?php
namespace Rebet\Auth\Event;

use Rebet\Auth\AuthUser;
use Rebet\Http\Request;

/**
 * Signin Event Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Signin
{
    /**
     * The request when this event occured.
     *
     * @var Request
     */
    public $request;

    /**
     * The authenticated user.
     *
     * @var AuthUser
     */
    public $user;

    /**
     * Indicates if the user should be "remembered".
     *
     * @var bool
     */
    public $remember;

    /**
     * Create an event
     *
     * @param Request $request
     * @param AuthUser $user
     * @param boolean $remember
     */
    public function __construct(Request $request, AuthUser $user, bool $remember)
    {
        $this->request  = $request;
        $this->user     = $user;
        $this->remember = $remember;
    }
}
