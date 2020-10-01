<?php
namespace Rebet\Http;

use PHPUnit\Framework\MockObject\BadMethodCallException;
use Rebet\Tools\Arrays;
use Rebet\Tools\Reflector;
use Rebet\Tools\Strings;
use Rebet\Http\Bag\FileBag;
use Rebet\Http\Exception\FallbackRedirectException;
use Rebet\Http\Response\RedirectResponse;
use Rebet\Http\Session\Session;
use Rebet\Routing\Router;
use Rebet\Validation\Validator;
use Rebet\Validation\ValidData;
use Rebet\View\View;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Request Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Request extends SymfonyRequest
{
    /**
     * Current request is the latest instantiated Request object.
     *
     * @var Request
     */
    protected static $current = null;

    /**
     * Route object matching routing
     *
     * @var Route
     */
    public $route = null;

    /**
     * {@inheritdoc}
     *
     * @param array                $query      The GET parameters
     * @param array                $request    The POST parameters
     * @param array                $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array                $cookies    The COOKIE parameters
     * @param array                $files      The FILES parameters
     * @param array                $server     The SERVER parameters
     * @param string|resource|null $content    The raw body data
     */
    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
        static::$current = $this;
    }

    /**
     * Clear the current request.
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$current = null;
    }

    /**
     * Get current request.
     *
     * @return Request|null
     */
    public static function current() : ?Request
    {
        return static::$current;
    }

    /**
     * Validate input data by given rules.
     *
     * @param string $crud
     * @param array|array[]|string|string[]|Rule|Rule[] $rules array(=map) of rule, string of Rule class name, Rule class instance and those lists.
     * @param string $fallback_url
     * @param bool $accept_undefined (default: false)
     * @return ValidData
     */
    public function validate(string $crud, $rules, string $fallback_url, bool $accept_undefined = false) : ValidData
    {
        $validator  = new Validator($this->all());
        $valid_data = $validator->validate($crud, $rules, $accept_undefined);
        if ($valid_data) {
            return $valid_data;
        }

        throw (new FallbackRedirectException('Validate Failed.'))->to($fallback_url)->with($this->input())->errors($validator->errors());
    }

    /**
     * Get all of the input and files for the request.
     *
     * @param string|null $key (default: null)
     * @param mixed $default (default: null)
     * @return mixed
     */
    public function all(?string $key = null, $default = null)
    {
        $all = array_replace_recursive($this->input(), $this->files());
        return Reflector::get($all, $key, $default);
    }

    /**
     * Get all of the input for the request.
     *
     * @param string|null $key (default: null)
     * @param mixed $default (default: null)
     * @return mixed
     */
    public function input(?string $key = null, $default = null)
    {
        return Reflector::get($this->request->all() + $this->query->all(), $key, $default);
    }

    /**
     * Get all of the files for the request.
     *
     * @param string|null $key (default: null)
     * @param mixed $default (default: null)
     * @return mixed
     */
    public function files(?string $key = null, $default = null)
    {
        return Reflector::get($this->files->all(), $key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        parent::initialize($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->files = new FileBag($this->files->all());
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(array $query = null, array $request = null, array $attributes = null, array $cookies = null, array $files = null, array $server = null)
    {
        $duplicate = parent::duplicate($query, $request, $attributes, $cookies, $files, $server);
        if ($files !== null) {
            $duplicate->files = new FileBag($duplicate->files->all());
        }
        return $duplicate;
    }

    /**
     * This method is unspported in Rebet.
     * You can use session() method to set the session instead.
     *
     * @see Request::session()
     * @deprecated Not unspported in Rebet.
     * @throws BadMethodCallException when the method was called.
     */
    public function getSession()
    {
        throw new BadMethodCallException("Request::getSession() method is unspported in Rebet. You can use Request::session() method to get the session instead.");
    }

    /**
     * This method is unspported in Rebet.
     * You can use session() method to set the session instead.
     *
     * @see Request::session()
     * @deprecated Not unspported in Rebet.
     * @throws BadMethodCallException when the method was called.
     */
    public function setSession(SessionInterface $session)
    {
        throw new BadMethodCallException("Request::setSession() method is unspported in Rebet. You can use Request::session() method to set the session instead.");
    }

    /**
     * This method is unspported in Rebet.
     * You can use session() method to set the session instead.
     *
     * @see Request::session()
     * @deprecated Not unspported in Rebet.
     * @throws BadMethodCallException when the method was called.
     */
    public function setSessionFactory(callable $factory)
    {
        throw new BadMethodCallException("Request::setSessionFactory() method is unspported in Rebet. You can use Request::session() method to set the session factory instead.");
    }

    /**
     * Get/Set the session for the request.
     *
     * @param Session|callable|null $session (default: null)
     * @return Session|self
     * @throws BadMethodCallException
     */
    public function session($session = null)
    {
        if ($session === null) {
            $this->session = is_callable($this->session) ? call_user_func($this->session) : $this->session ;

            if (null === $this->session) {
                throw new \BadMethodCallException('Session has not been set');
            }

            return $this->session;
        }

        $this->session = $session;
        return $this;
    }

    /**
     * Get the bearer token from the request headers.
     *
     * @return string|null
     */
    public function bearerToken()
    {
        $header = $this->headers->get('Authorization', '');
        if (Strings::startsWith($header, 'Bearer ')) {
            return \mb_substr($header, 7);
        }
    }

    /**
     * Get request URI without query.
     *
     * @param bool $withoutPrefix (default: false)
     * @return void
     */
    public function getRequestPath(bool $withoutPrefix = false) : string
    {
        $request_path = Strings::latrim($this->getRequestUri(), '?');
        return $withoutPrefix ? Strings::ltrim($request_path, $this->getRoutePrefix(), 1) : $request_path ;
    }

    /**
     * Get route prefix.
     * If the route is not set then return prefix from request path.
     *
     * @return string
     * @throws LogicException
     */
    public function getRoutePrefix() : string
    {
        if (!$this->route) {
            return Router::getPrefixFrom($this->getRequestPath()) ?? '';
        }
        return $this->route->prefix ?? '';
    }

    /**
     * Get user agent.
     *
     * @return string|null
     */
    public function getUserAgent() : UserAgent
    {
        return UserAgent::valueOf($this->headers->get('User-Agent')) ;
    }

    /**
     * Save the request data to session with given name.
     *
     * @see self::replay()
     * @param string $name
     * @return self
     */
    public function saveAs(string $name) : self
    {
        $this->session()->flash()->set("_request_{$name}", [
            'uri'   => $this->getRequestUri(),
            'input' => $this->input(),
        ]);
        return $this;
    }

    /**
     * It checks given name request data is saved.
     *
     * @param string $name
     * @return boolean
     */
    public function isSaved(string $name) : bool
    {
        return $this->session()->flash()->has("_request_{$name}") ;
    }

    /**
     * Replay the saved request of given name using redirect.
     *
     * @param string $name
     * @param array $append_query
     * @param integer $status
     * @param array $headers
     * @return RedirectResponse|null
     */
    public function replay(string $name, array $append_query = [], int $status = 302, array $headers = []) : ?RedirectResponse
    {
        if ($this->isSaved($name)) {
            $saved = $this->session()->flash()->get("_request_{$name}");
            return Responder::redirect('@'.$saved['uri'], $append_query, $status, $headers, $this)->with($saved['input'] ?? []);
        }
        return null;
    }

    /**
     * Restore the inherit data from sesssion saved data (if exists).
     *
     * @see RestoreRedirectInput middleware
     * @return self
     */
    public function restoreInheritData() : self
    {
        $request_path = $this->getRequestPath(true);
        $session      = $this->session();
        $this->request->add($session->loadInheritData('input', $request_path));
        View::share('errors', $session->loadInheritData('errors', $request_path));
        return $this;
    }

    /**
     * Inherit input data to next request.
     *
     * @param string|array $wildcard of request path (default: '*')
     * @return self
     */
    public function inheritInputTo($wildcard = '*') : self
    {
        $this->session()->saveInheritData('input', $this->input(), $wildcard);
        return $this;
    }

    /**
     * Determine if the current request probably expects a JSON response.
     *
     * @return bool
     */
    public function expectsJson() : bool
    {
        return ($this->isAjax() && ! $this->isPjax() && $this->acceptsAnyContentType()) || $this->wantsJson();
    }

    /**
     * Determine if the current request is asking for JSON.
     *
     * @return bool
     */
    public function wantsJson()
    {
        $acceptable = $this->getAcceptableContentTypes();
        return Strings::contains($acceptable[0] ?? null, ['/json', '+json'], 1);
    }

    /**
     * Determine if the request is the result of an AJAX call.
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->isXmlHttpRequest();
    }

    /**
     * Determine if the request is the result of an PJAX call.
     *
     * @return bool
     */
    public function isPjax()
    {
        return $this->getHeader('X-PJAX', true) == true || $this->query->get('_pjax') == true ;
    }

    /**
     * Determine if the current request accepts any content type.
     *
     * @return bool
     */
    public function acceptsAnyContentType()
    {
        $acceptable = $this->getAcceptableContentTypes();
        $wants      = $acceptable[0] ?? null;
        return count($acceptable) === 0 || ($wants === '*/*' || $wants === '*');
    }

    /**
     * Get header values.
     *
     * @param string $key
     * @param boolean $first (default: false)
     * @return string|string[]|null
     */
    public function getHeader(string $key, bool $first = false)
    {
        return $first ? $this->headers->get($key) : Arrays::peel($this->headers->all($key));
    }

    /**
     * Set a header on the Response.
     *
     * @param string $key
     * @param array|string $values
     * @param boolean $replace (default: true)
     * @return self
     */
    public function setHeader(string $key, $values, bool $replace = true) : self
    {
        $this->headers->set($key, $values, $replace);
        return $this;
    }
}
