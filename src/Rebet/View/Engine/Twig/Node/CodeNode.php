<?php
namespace Rebet\View\Engine\Twig\Node;

use Rebet\Common\Reflector;
use Twig\Compiler;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;

/**
 * CodeNode Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class CodeNode extends Node
{
    /**
     * @var array of callbacks
     */
    protected static $callbacks = [];

    /**
     * Clear registered callbacks.
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$callbacks = [];
    }

    /**
     * Add callback named given name.
     *
     * @param string $name
     * @param callable $callback
     * @return void
     */
    public static function addCallback(string $name, callable $callback) : void
    {
        static::$callbacks[$name] = \Closure::fromCallable($callback);
    }

    /**
     * Execute given name callback.
     *
     * @param string $name
     * @param array $args (default: [])
     * @return mixed
     */
    public static function execute(string $name, array $args = [])
    {
        return isset(static::$callbacks[$name]) ? Reflector::evaluate(static::$callbacks[$name], $args, true) : null ;
    }

    /**
     * Create Code Node
     *
     * @param string $open
     * @param string $name
     * @param array $args
     * @param string $close
     * @param array $binds (default: [])
     * @param bool $invert (default: false)
     * @param int $lineno (default: 0)
     */
    public function __construct(string $open, string $name, array $args, string $close, array $binds = [], bool $invert = false, int $lineno = 0)
    {
        $elements = [];
        $args     = array_merge(
            array_map(function ($value) use ($lineno) { return new NameExpression($value, $lineno); }, $binds),
            array_map(function ($value) use ($lineno) { return $value instanceof Node ? $value : new ConstantExpression($value, $lineno); }, $args)
        );
        foreach ($args as $key => $value) {
            $elements[] = new ConstantExpression($key, $lineno);
            $elements[] = $value;
        }

        parent::__construct(
            [
                'name' => new ConstantExpression($name, $lineno),
                'args' => new ArrayExpression($elements, $lineno),
            ],
            [
                'open'   => $open,
                'close'  => $close,
                'invert' => $invert,
            ],
            $lineno,
            $name
        );
    }

    /**
     * {@inheritDoc}
     */
    public function compile(Compiler $compiler)
    {
        $invert = $this->getAttribute('invert');
        $compiler
            ->addDebugInfo($this)
            ->raw($this->getAttribute('open'))
            ->raw($invert ? '!(' : '')
            ->raw(" Rebet\\View\\Engine\\Twig\\Node\\CodeNode::execute(")
            ->subcompile($this->getNode('name'))
            ->raw(", ")
            ->subcompile($this->getNode('args'))
            ->raw(") ")
            ->raw($invert ? ')' : '')
            ->raw($this->getAttribute('close'))
            ;
    }
}
