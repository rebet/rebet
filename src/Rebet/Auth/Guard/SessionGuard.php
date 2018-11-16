<?php
namespace Rebet\Auth\Guard;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Provider\AuthProvider;
use Rebet\Config\Configurable;
use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Session\Session;
use Rebet\Common\Securities;

/**
 * Session Guard Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SessionGuard implements Guard
{
    use Configurable, Guardable;

    public static function defaultConfig() : array
    {
        return [
            'fields' => [
                'signin_id' => 'email',
                'password'  => 'password',
                'remember'  => 'remember_me',
            ],
            'remember_days' => 0,
        ];
    }

    /**
     * Cookie key of remember token.
     */
    const COOKIE_KEY_REMEMBER = 'remember';

    /**
     * Number of days to remember me.
     *
     * @var int
     */
    private $remember_days = null;

    /**
     * Create a session guard.
     *
     * @param integer|null $remember_days (default: depend on configure)
     */
    public function __construct(?int $remember_days = null)
    {
        $this->remember_days = $remember_days ?? static::config('remember_days');
    }

    /**
     * Create a session key from a given name for this guard.
     *
     * @param string $name
     * @return string
     */
    protected function toSessionKey(string $name) : string
    {
        return 'auth:'.$this->authenticator().':'.$name;
    }

    /**
     * {@inheritDoc}
     */
    public function signin(Request $request, array $credentials, AuthProvider $provider, callable $checker, bool $remember = false) : AuthUser
    {
        $signin_id = $credentials['signin_id'];
        $password  = $this->password_hasher($credentials['password']);

        $user = $provider->findBySigninId($signin_id, function ($user) use ($checker, $password) { return $checker($user, $password); });
        if (!$user) {
            return AuthUser::guest();
        }

        $session->set($this->toSessionKey('id'), $user->id());
        if ($remember) {
            $token = $provider->issuingRememberToken($id, $this->remember_days);
            Cookie::set(COOKIE_KEY_REMEMBER, $token, $this->remember_days === 0 ? 0 : "+{$this->remember_days} day");
        }

        return $user;
    }
    
    /**
     * {@inheritDoc}
     */
    public function signout(Request $request, AuthProvider $provider, AuthUser $user, string $redirect_to) : Response
    {
        $session = $request->getSession();
        $session->remove($this->toSessionKey('id'));
        Cookie::remove(COOKIE_KEY_REMEMBER);
        return Responder::redirect($redirect_to);
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(Request $request, AuthProvider $provider, callable $checker) : AuthUser
    {
        $session = $request->getSession();
        $id      = $session->get($this->toSessionKey('id'));
        $user    = $provider->findById($id, function ($user) use ($checker) { return $checker($user, null); });
        if ($user) {
            return $user;
        }
        $token = $request->cookies->get(COOKIE_KEY_REMEMBER);
        $user  = $token ? $provider->findByRememberToken($token) : $user ;
        return $user ? $user : AuthUser::guest();
    }
}
