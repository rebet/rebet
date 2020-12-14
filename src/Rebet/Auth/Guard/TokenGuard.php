<?php
namespace Rebet\Auth\Guard;

use Rebet\Auth\Auth;
use Rebet\Auth\AuthUser;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Response;

/**
 * Token Guard Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class TokenGuard extends Guard
{
    /**
     * Request of current session.
     *
     * @var Request
     */
    protected $request;

    /**
     * Input key name.
     *
     * @var string
     */
    protected $input_key;

    /**
     * Create a token guard.
     *
     * @param string $provider name of configured in `Auth.providers.{name}`.
     * @param string $input_key (default: 'api_token')
     * @param Request $request (default: null for Request::current())
     */
    public function __construct(string $provider, string $input_key = 'api_token', ?Request $request = null)
    {
        parent::__construct($provider);
        $this->input_key = $input_key;
        $this->request   = $request ?? Request::current();
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate() : ?Response
    {
        $this->user    = $this->provider->findByToken($this->token()) ?? AuthUser::guest() ;
        $allowed_roles = $this->request->route->roles() ?: [];
        return $this->user->is(...$allowed_roles) ? null : $this->fallback() ;
    }

    /**
     * Get token from current request.
     *
     * @return string|null
     */
    protected function token() : ?string
    {
        return $this->request->input($this->input_key) ?:
               $this->request->bearerToken() ?:
               $this->request->getPassword()
               ;
    }

    /**
     * Create fallback response.
     *
     * @return Response
     */
    protected function fallback() : Response
    {
        return Responder::problem(403);
    }
}
