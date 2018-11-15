<?php
namespace Rebet\Auth\Event;

use Rebet\Auth\AuthUser;
use Rebet\Http\Request;

/**
 * Signout Event Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Signout
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
     * Create an event
     *
     * @param Request $request
     * @param AuthUser $user
     */
    public function __construct(Request $request, AuthUser $user)
    {
        $this->request = $request;
        $this->user    = $user;
    }
}
