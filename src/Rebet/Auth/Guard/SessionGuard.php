<?php
namespace Rebet\Auth\Guard;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Exception\AuthenticateException;
use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Response;

/**
 * Session Guard Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SessionGuard extends StatefulGuard
{
    /**
     * Request of current session.
     *
     * @var Request
     */
    protected $request;

    /**
     * Fallback url or null for throw AuthenticateException.
     *
     * @var string|null
     */
    protected $fallback = null;

    /**
     * Create a session guard.
     *
     * @param string $provider name of configured in `Auth.providers.{name}`.
     * @param string|null $fallback redirect url. (default: null for throw AuthenticateException)
     * @param int $remember_days (default: 0)
     * @param Request $request (default: null for Request::current())
     */
    public function __construct(string $provider, ?string $fallback = null, int $remember_days = 0, ?Request $request = null)
    {
        parent::__construct($provider, $remember_days);
        $this->fallback = $fallback;
        $this->request  = $request ?? Request::current();
    }

    /**
     * Get a remember token key for cookie.
     *
     * @return string
     */
    protected function rememberTokenKey() : string
    {
        return "auth:{$this->name}:remember_token";
    }

    /**
     * Get a signin ID key for session.
     *
     * @return string
     */
    protected function signinIdKey() : string
    {
        return "auth:{$this->name}:signin_id";
    }

    /**
     * Request Replay Session Key for replay original request that guared by auth when signin success.
     *
     * @return string
     */
    protected function requestReplayKey() : string
    {
        return "auth:{$this->name}:guarded_request";
    }

    /**
     * {@inheritDoc}
     */
    public function signin(AuthUser $user, string $goto = '/', bool $remember = false) : Response
    {
        if ($user->isGuest()) {
            return $this->fallback();
        }

        if ($user->provider() !== $this->provider) {
            throw new AuthenticateException("Can not sign-in by authenticated user who is got by different provider.");
        }

        $this->user = $user;
        $this->request->session()->set($this->signinIdKey(), $user->id);
        if ($remember && $this->provider->supportRememberToken()) {
            $token = $this->provider->issuingRememberToken($user->id, $this->remember_days);
            if ($token !== null) {
                Cookie::set($this->rememberTokenKey(), $token, $this->remember_days === 0 ? 0 : "+{$this->remember_days} day");
            }
        }

        return $this->request->replay($this->requestReplayKey()) ?? Responder::redirect($goto);
    }

    /**
     * {@inheritDoc}
     */
    public function signout(string $goto = '/') : Response
    {
        if (!$this->user()->isGuest()) {
            if ($this->provider->supportRememberToken()) {
                $this->provider->removeRememberToken($this->request->cookies->get($remember_token_key = $this->rememberTokenKey()));
                Cookie::remove($remember_token_key);
            }
            $this->request->session()->remove($this->signinIdKey());
            $this->user    = AuthUser::guest();
        }

        return Responder::redirect($goto);
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate() : ?Response
    {
        $user = $this->provider->findById($this->request->session()->get($this->signinIdKey()));
        if ($user === null && $this->provider->supportRememberToken()) {
            $user = $this->provider->findByRememberToken($this->request->cookies->get($this->rememberTokenKey()));
        }
        $this->user    = $user ?? AuthUser::guest() ;
        $allowed_roles = $this->request->route->roles() ?: [];
        return $this->user->is(...$allowed_roles) ? null : $this->fallback() ;
    }

    /**
     * Create fallback response.
     *
     * @return Response
     * @throws AuthenticateException when fallback is undefined.
     */
    protected function fallback() : Response
    {
        if (!$this->fallback) {
            throw new AuthenticateException("Authentication failed and specific fallback was not defined.");
        }
        $this->request->saveAs($this->requestReplayKey());
        return Responder::redirect($this->fallback);
    }
}
