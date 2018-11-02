<?php
namespace Rebet\Http;

use Rebet\Common\Reflector;
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
     * ルーティングにマッチしたルートオブジェクト
     *
     * @var Route
     */
    public $route = null;

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

    public function validate($forms, string $crud, string $role, string $fallback_url, array $option = [])
    {
        $valid  = [];
        $errors = [];
        foreach ((array)$forms as $form) {
            $form = Reflector::instantiate($form);
            $form->popurate(array_merge($this->query->all(), $this->request->all()), $this->files->all());
            $errors  = array_merge($errors, $form->validate($crud, $role, $option));
            $valid[] = $form;
        }

        if (!empty($errors)) {
            throw new FallbackException($this, $errors, $fallback_url);
        }

        return count($valid) === 1 ? $valid[0] : $valid ;
    }
}
