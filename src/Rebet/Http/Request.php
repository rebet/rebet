<?php
namespace Rebet\Http;

use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Exception\FallbackRedirectException;
use Rebet\Http\Session\Session;
use Rebet\Routing\Router;
use Rebet\Validation\Validator;
use Rebet\Validation\ValidData;
use Rebet\View\View;
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
     * @see SetChannelToRequest Middleware
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

        throw FallbackRedirectException::by('Validate Failed.')->to($fallback_url)->with($this->input())->errors($validator->errors());
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
        return new UserAgent($this->headers->get('User-Agent'));
    }

    /**
     * Save the request data to session with given name.
     *
     * @param string $name
     * @return self
     */
    public function saveAs(string $name) : self
    {
        $this->session()->flash()->set("_request_{$name}", [
            'uri'  => $this->getRequestUri(),
            'post' => $this->request->all(),
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
     * @return Response|null
     */
    public function replay(string $name, array $append_query = [], int $status = 302, array $headers = []) : ?Response
    {
        if ($this->isSaved($name)) {
            $saved = $this->session()->flash()->get("_request_{$name}");
            return Responder::redirect('@'.$saved['uri'], $append_query, $status, $headers, $this)->with($saved['post'] ?? []);
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
        $this->request->add($session->loadInheritData('input', $request_path, []));
        View::share('errors', $session->loadInheritData('errors', $request_path, []));
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
        return Strings::contains($acceptable[0] ?? null, ['/json', '+json']);
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
        return $this->headers->get('X-PJAX') == true;
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
}
