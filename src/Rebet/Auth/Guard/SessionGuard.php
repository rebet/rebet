<?php
namespace Rebet\Auth\Guard;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Provider\AuthProvider;
use Rebet\Config\Configurable;
use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Response;
use Rebet\Http\Session\Session;

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
    public function signin(Request $request, AuthUser $user, bool $remember = false) : void
    {
        if ($user->isGuest()) {
            return;
        }

        $session = $request->getSession();
        $session->set($this->toSessionKey('id'), $user->id);
        $provider = $user->provider();
        if ($remember && $provider->supportRememberToken()) {
            $token = $provider->issuingRememberToken($user->id, $this->remember_days);
            if ($token !== null) {
                Cookie::set(static::COOKIE_KEY_REMEMBER, $token, $this->remember_days === 0 ? 0 : "+{$this->remember_days} day");
            }
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function signout(Request $request, AuthUser $user, string $redirect_to) : Response
    {
        if (!$user->isGuest()) {
            $provider = $user->provider();
            if ($provider->supportRememberToken()) {
                $provider->removeRememberToken($request->cookies->get(static::COOKIE_KEY_REMEMBER));
            }
            $request->getSession()->remove($this->toSessionKey('id'));
            Cookie::remove(static::COOKIE_KEY_REMEMBER);
        }
        return Responder::redirect($redirect_to);
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(Request $request, AuthProvider $provider) : AuthUser
    {
        $session = $request->getSession();
        $id      = $session->get($this->toSessionKey('id'));
        $user    = $provider->findById($id);
        if ($user) {
            return $user;
        }
        if ($provider->supportRememberToken()) {
            $user = $provider->findByRememberToken($request->cookies->get(static::COOKIE_KEY_REMEMBER));
        }
        return $user ? $user : AuthUser::guest();
    }

    /**
     * Get the 'remember me' days period.
     *
     * @return integer of days
     */
    public function getRememberDays() : int
    {
        return $this->remember_days;
    }
}
