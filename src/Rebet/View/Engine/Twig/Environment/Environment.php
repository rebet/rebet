<?php
namespace Rebet\View\Engine\Twig\Environment;

use Rebet\View\Engine\Twig\Parser\CodeTokenParser;
use Rebet\View\Engine\Twig\Parser\RawTokenParser;
use Twig\Environment as TwigEnvironment;

/**
 * Environment Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Environment extends TwigEnvironment
{
    /**
     * @var boolean of registered else tag
     */
    protected $registered_else = false;

    /**
     * Register an "raw" extention.
     *
     * @param string $tag
     * @param string $code
     * @return void
     */
    public function raw(string $tag, string $code) : void
    {
        $this->addTokenParser(new RawTokenParser($tag, $code));
    }

    /**
     * Register an "code" extention.
     * If you give '$errors' as binds then you can get the $errors of assigned value as first argument of callback.
     *
     * @param string $name
     * @param string|null $verbs
     * @param array|null $separators of arguments. If null given then [','] will set.
     * @param string $open code to callbak returns like 'echo(', '$var =', 'if(', '' etc
     * @param callable $callback
     * @param string $close code to callbak returns like ');', ';', '):' etc
     * @param array $binds (default: null)
     * @return void
     */
    public function code($name, ?string $verbs, ?array $separators, string $open, callable $callback, string $close, array $binds = []) : void
    {
        $this->addTokenParser(new CodeTokenParser($name, $verbs, $separators, $open, $callback, $close, $binds));
    }

    /**
     * Register an "if" (and not) extention.
     *
     * @param string $name
     * @param string|null $verbs
     * @param array|null $separators of arguments. If null given then [','] will set.
     * @param callable $callback
     * @return void
     */
    public function if(string $name, ?string $verbs, ?array $separators, callable $callback)
    {
        $this->code($name, $verbs, $separators, 'if(', $callback, ") {\n");
        $this->code("else{$name}", $verbs, $separators, '} elseif(', $callback, ") {\n");
        if (!$this->registered_else) {
            $this->raw("else", "} else {\n");
            $this->registered_else = true;
        }
        $this->raw("end{$name}", "}\n");
    }
}
