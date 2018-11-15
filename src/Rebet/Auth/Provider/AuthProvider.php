<?php
namespace Rebet\Auth\Provider;

use Rebet\Auth\AuthUser;
use Rebet\Common\Securities;

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
    abstract public function findById($id, ?callable $checker = null) : ?AuthUser ;

    abstract public function findBySigninId($signin_id, ?callable $checker = null) : ?AuthUser ;

    abstract public function findByToken(string $token, ?callable $checker = null) : ?AuthUser ;

    abstract public function rememberToken($id, string $token, int $effective_days) : void ;

    public function generateToken(int $length = 40) : string
    {
        return Securities::randomCode($length);
    }
}
