<?php
namespace Rebet\Auth\Provider;

use Rebet\Auth\AuthUser;


/**
 * Abstract Auth Provider Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class AuthProvider
{
    public abstract function findById($id) : ?AuthUser ;

    public abstract function findByToken(string $token, $id = null) : ?AuthUser ;

    public abstract function updateToken($id, string $token) : void ;
}
