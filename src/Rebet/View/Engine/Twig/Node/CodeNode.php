<?php
namespace Rebet\View\Engine\Twig\Node;

use Twig\Compiler;
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
     * @param mixed ...$args
     * @return mixed
     */
    public static function execute(string $name, ...$args)
    {
        return isset(static::$callbacks[$name]) ? call_user_func(static::$callbacks[$name], ...$args) : null ;
    }

    /**
     * Create Code Node
     *
     * @param string $open
     * @param string $name
     * @param \Twig_Node $template_args
     * @param string $close
     * @param array $binds (default: [])
     * @param int $lineno (default: 0)
     */
    public function __construct(string $open, string $name, \Twig_Node $template_args, string $close, array $binds = [], int $lineno = 0)
    {
        $args   = [];
        $args[] = new ConstantExpression($name, $lineno);
        foreach ($binds ?? [] as $arg) {
            $args[] = new NameExpression($arg, $lineno);
        }
        foreach ($template_args as $arg) {
            $args[] = $arg;
        }
        parent::__construct(
            ['args' => $this->toArgsNode($args, $lineno, $name)],
            [
                'open'  => $open,
                'close' => $close,
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
        $compiler
            ->addDebugInfo($this)
            ->raw($this->getAttribute('open'))
            ->raw(" Rebet\\View\\Engine\\Twig\\Node\\CodeNode::execute(")
            ->subcompile($this->getNode('args'))
            ->raw(") ")
            ->raw($this->getAttribute('close'))
            ;
    }

    /**
     * Add argument delimiter nodes.
     *
     * @param array $args
     * @param integer $lineno
     * @param string $tag
     * @return void
     */
    protected function toArgsNode(array $args, int $lineno, string $tag) : \Twig_Node
    {
        $delimiter = new RawNode(', ', $lineno, $tag);
        $nodes     = [];
        foreach ($args as $arg) {
            $nodes[] = $arg;
            $nodes[] = $delimiter;
        }
        unset($nodes[count($nodes) - 1]);
        return new Node($nodes, [], $lineno, $tag);
    }
}
