<?php
declare(strict_types=1);

namespace Rebet\Middleware\Routing;

use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Tools\Utility\Arrays;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * [Routing Middleware] Input Data Transform Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class InputDataTransform
{
    /**
     * @var array<int, string>
     */
    protected $except = [];

    /**
     * Create Input Data Transform Middleware
     *
     * @param string ...$except
     */
    public function __construct(string ...$except)
    {
        $this->except = $except;
    }

    /**
     * Handle Input Data Transform Middleware.
     *
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle(Request $request, \Closure $next) : Response
    {
        $this->transformBag($request->request);
        $this->transformBag($request->query);
        return $next($request);
    }

    /**
     * Transform the value of given bag.
     *
     * @param ParameterBag $bag
     * @return void
     */
    protected function transformBag(ParameterBag $bag)
    {
        $bag->replace($this->transformArray($bag->all()));
    }

    /**
     * Transform the value of given value.
     *
     * @param array<int|string, mixed> $array
     * @param string $prefix (default: '')
     * @return array<int|string, mixed>
     */
    protected function transformArray(array $array, string $prefix = '') : array
    {
        return Arrays::map($array, function ($value, $key) use ($prefix) {
            $key = is_int($key) ? $prefix : ($prefix === '' ? $key : "{$prefix}.{$key}") ;
            return $this->transformValue($key, $value);
        });
    }

    /**
     * Transform the value of given value.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function transformValue($key, $value)
    {
        if (in_array($key, $this->except, true)) {
            return $value;
        }
        return is_array($value) ? $this->transformArray($value, $key) : $this->transform($key, $value) ;
    }

    /**
     * Transform the given value.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    abstract protected function transform($key, $value);
}
