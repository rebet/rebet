<?php
namespace Rebet\Http;

use Rebet\Common\Reflector;
use Rebet\Common\Securities;
use Rebet\Common\Strings;
use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Session\Session;
use Rebet\Validation\Validator;
use Rebet\Validation\ValidData;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

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
     * The channel of this request incoming
     *
     * @see SetChannel Middleware
     * @var string
     */
    public $channel = null;

    /**
     * Can the client use cookie
     * Note: This flag does not guarantee the correctness of being unusable because it is always false at first access.
     *
     * @var boolean
     */
    public $can_use_cookie = false;

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
        if ($this->cookies->has('_beacon')) {
            $this->can_use_cookie = true;
        } else {
            Cookie::set('_beacon', 1);
        }
    }

    /**
     * Get current request.
     *
     * @return Request
     */
    public static function current() : Request
    {
        return static::$current;
    }

    /**
     * Validate input data by given rules.
     *
     * @param string $crud
     * @param string|Rule|array $rules
     * @param string $fallback_url
     * @return ValidData
     */
    public function validate(string $crud, $rules, string $fallback_url) : ValidData
    {
        $validator  = new Validator($this->all());
        $valid_data = $validator->validate($crud, $rules);
        if ($valid_data) {
            return $valid_data;
        }

        throw new FallbackException($this, $validator->errors(), $fallback_url);
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
        $input = in_array($this->getMethod(), ['GET', 'HEAD']) ? $this->query->all() : $this->request->all() + $this->query->all();
        return Reflector::get($input, $key, $default);
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
        if (!empty($files)) {
            $this->files = new FileBag($this->convertUploadedFiles($this->files->all()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(array $query = null, array $request = null, array $attributes = null, array $cookies = null, array $files = null, array $server = null)
    {
        $duplicate = parent::duplicate($query, $request, $attributes, $cookies, $files, $server);
        if ($files !== null) {
            $duplicate->files = new FileBag($this->convertUploadedFiles($duplicate->files->all()));
        }
        return $duplicate;
    }
    
    /**
     * Convert the given array of Symfony UploadedFiles to Rebet UploadedFiles.
     *
     * @param array $files
     * @return array
     */
    protected function convertUploadedFiles(array $files) : array
    {
        return array_map(function ($file) {
            return is_array($file) ? $this->convertUploadedFiles($file) : UploadedFile::valueOf($file);
        }, $files);
    }

    /**
     * Set the Rebet Session for the request.
     *
     * @param Session $session
     * @return self
     */
    public function setRebetSession(Session $session) : self
    {
        $this->session = $session;
        return $this;
    }

    /**
     * Get the session for the request
     *
     * @return Session
     * @throws BadMethodCallException
     */
    public function getSession()
    {
        $session = $this->session;
        if (is_callable($session)) {
            $this->setSession($session = $session());
        }

        if (null === $session) {
            throw new \BadMethodCallException('Session has not been set');
        }

        return $session;
    }

    /**
     * Get the session for the request (alias of getSession())
     *
     * @return Session
     * @throws BadMethodCallException
     */
    public function session() : Session
    {
        return $this->getSession();
    }

    /**
     * Get the bearer token from the request headers.
     *
     * @return string|null
     */
    public function bearerToken()
    {
        $header = $this->header('Authorization', '');
        if (Strings::startsWith($header, 'Bearer ')) {
            return Strings::substr($header, 7);
        }
    }

    /**
     * Get request URI without query.
     *
     * @return void
     */
    public function getRequestPath() : string
    {
        return Strings::latrim($this->getRequestUri(), '?');
    }

    /**
     * Get route prefix.
     * If the route is not set then throw LogicException.
     *
     * @return string
     * @throws LogicException
     */
    public function getRoutePrefix() : string
    {
        if (!$this->route) {
            throw new \LogicException("Route is not selected for this request.");
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
        return new UserAgent($this->headers->get('User-Agent'));
    }

    /**
     * Save the request data to session with given name.
     *
     * @param string $name
     * @param int $hash_length (default: 12)
     * @return self
     */
    public function saveAs(string $name, int $hash_length = 12) : self
    {
        if ($this->session()) {
            $this->session()->set("_request:{$name}:hash", Securities::randomCode($hash_length));
            $this->session()->set("_request:{$name}:uri", $this->getRequestUri());
            $this->session()->set("_request:{$name}:request", $this->request->all());
        }
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
        return $this->session() ? $this->session()->has("_request:{$name}:uri") : false;
    }

    /**
     * Redirect to uri same as saved request of given name.
     *
     * @param string $name
     * @param array $append_query
     * @param integer $status
     * @param array $headers
     * @return Response|null
     */
    public function redirectTo(string $name, array $append_query = [], int $status = 302, array $headers = []) : ?Response
    {
        if ($this->isSaved($name)) {
            if ($this->can_use_cookie) {
                Cookie::set("_{$name}", $this->session()->get("_request:{$name}:hash"));
            } else {
                $append_query = array_merge($append_query, [
                    "_{$name}" => $this->session()->get("_request:{$name}:hash")
                ]);
            }
            return Responder::redirect($this->session()->get("_request:{$name}:uri"), $append_query, $status, $headers, $this);
        }
        return null;
    }

    /**
     * Restore the request from given name saved request data.
     *
     * @param string $name
     * @return self
     */
    public function restoreFrom(string $name) : self
    {
        if ($this->isSaved($name)) {
            $hash = $this->cookies->get("_{$name}") ?? $this->query->get("_{$name}");
            if ($hash === $this->session()->get("_request:{$name}:hash")) {
                $this->request->replace($this->session()->get("_request:{$name}:request"));
                $this->remove($name);
            }
        }
        return $this;
    }

    /**
     * Remove the given name saved request data.
     *
     * @param string $name
     * @return self
     */
    public function remove(string $name) : self
    {
        if ($this->isSaved($name)) {
            $this->session()->remove("_request:{$name}:hash");
            $this->session()->remove("_request:{$name}:uri");
            $this->session()->remove("_request:{$name}:request");
            Cookie::remove("_{$name}");
        }
    }
}
