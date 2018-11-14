<?php
namespace Rebet\Http;

use Rebet\Common\Reflector;
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
}
