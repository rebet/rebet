<?php
namespace Rebet\Auth\Guard;

use Rebet\Http\Request;
use Rebet\Auth\AuthUser;
use Rebet\Auth\Provider\AuthProvider;


/**
 * Guard Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Guard
{
    public function authenticate(Request $request, AuthProvider $provider, bool $remember = false) : AuthUser;

    
    public function recall(Request $request, AuthProvider $provider) : AuthUser;
}
