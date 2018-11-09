<?php
namespace Rebet\Http;

use Rebet\Common\Reflector;
use Rebet\Validation\Validator;
use Rebet\Validation\ValidData;
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
     * Undocumented variable
     *
     * @var array
     */
    protected $converted_files = null;

    /**
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
     * Undocumented function
     *
     * @param string $crud
     * @param array|string|Rule $rules
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
        $files = $this->converted_files ?? $this->converted_files = $this->convertUploadedFiles($this->files->all());
        return Reflector::get($files, $key, $default);
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
}
