<?php
namespace Rebet\Auth\Guard;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Provider\AuthProvider;
use Rebet\Config\Configurable;
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
     * This method nothing to do.
     *
     * {@inheritDoc}
     */
    public function signin(Request $request, AuthUser $user, bool $remember = false) : void
    {
        // Nothing to do
    }
    
    /**
     * This method nothing to do and always return JsonResponse of {"result":true}.
     *
     * {@inheritDoc}
     */
    public function signout(Request $request, AuthUser $user, string $redirect_to = '/') : Response
    {
        // Nothing to do (Just return JsonResponse)
        return Responder::toResponse(['result' => true]);
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(Request $request, AuthProvider $provider) : AuthUser
    {
        $user = $provider->findByToken($this->storage_key, $this->getTokenFrom($request));
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
