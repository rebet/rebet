<?php
namespace Rebet\Auth\Guard;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Provider\AuthProvider;
use Rebet\Config\Configurable;
use Rebet\Http\Request;

/**
 * Token Guard Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class TokenGuard implements Guard
{
    use Configurable, Guardable;

    public static function defaultConfig() : array
    {
        return [
            'input_key'   => 'api_token',
            'storage_key' => 'api_token',
        ];
    }

    /**
     * Input key name.
     *
     * @var string
     */
    protected $input_key = null;

    /**
     * Storage key name.
     *
     * @var string
     */
    protected $storage_key = null;

    /**
     * Create a token guard.
     *
     * @param string|null $input_key (default: depend on configure)
     * @param string|null $storage_key (default: depend on configure)
     */
    public function __construct(?string $input_key = null, ?string $storage_key = null)
    {
        $this->input_key   = $input_key ?? static::config('input_key');
        $this->storage_key = $storage_key ?? static::config('storage_key');
    }

    /**
     * {@inheritDoc}
     */
    public function signin(Request $request, AuthUser $user, bool $remember = false) : void
    {
        throw new \BadMethodCallException("TokenGuard not supported signin() function.");
    }
    
    /**
     * {@inheritDoc}
     */
    public function signout(Request $request, AuthUser $user, string $redirect_to) : Response
    {
        throw new \BadMethodCallException("TokenGuard not supported signout() function.");
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(Request $request, AuthProvider $provider) : AuthUser
    {
        $user = $provider->findByCredentials([$this->storage_key, $this->getTokenFrom($request)]);
        return $user ? $user : AuthUser::guest();
    }

    /**
     * Get token from given request.
     *
     * @param Request $request
     * @return string|null
     */
    protected function getTokenFrom(Request $request) : ?string
    {
        return $request->input($this->input_key) ?:
               $request->bearerToken() ?:
               $request->getPassword()
               ;
    }
}