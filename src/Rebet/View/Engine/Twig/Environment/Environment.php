<?php
namespace Rebet\View\Engine\Twig\Environment;

use Rebet\View\Code\Code;
use Rebet\View\Engine\Twig\Parser\EmbedTokenParser;
use Rebet\View\Engine\Twig\Parser\RawTokenParser;
use Rebet\View\Tag\Processor;
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
     * Register an "embed" extention.
     * If you give '$errors' as binds then you can get the $errors of assigned value as first argument of Processor::execute().
     *
     * @param string $name
     * @param string|null $verbs
     * @param array|null $separators of arguments. If null given then the code tag do not take arguments and empty array for takes only one argument.
     * @param string $open code to callbak returns like 'echo(', '$var =', 'if(', '' etc
     * @param Processor $processor
     * @param string $close code to callbak returns like ');', ';', '):' etc
     * @param array $binds (default: [])
     * @param bool $can_omit_first_arg (default: false)
     * @return void
     */
    public function embed($name, ?string $verbs, ?array $separators, string $open, Processor $processor, string $close, array $binds = [], bool $can_omit_first_arg = false) : void
    {
        $this->addTokenParser(new EmbedTokenParser($name, $verbs, $separators, $open, $processor, $close, $binds, $can_omit_first_arg));
    }

    /**
     * Register an "if" (and not) extention.
     *
     * @param string $name
     * @param string|null $verbs
     * @param array|null $separators of arguments. If null given then the code tag do not take arguments and empty array for takes only one argument.
     * @param Processor $processor
     * @param array $binds (default: [])
     * @param bool $can_omit_first_arg (default: false)
     * @return void
     */
    public function case(string $name, ?string $verbs, ?array $separators, Processor $processor, array $binds = [], bool $can_omit_first_arg = false)
    {
        $this->embed($name, $verbs, $separators, 'if(', $processor, ") {\n", $binds, $can_omit_first_arg);
        $this->embed("else{$name}", $verbs, $separators, '} elseif(', $processor, ") {\n", $binds, $can_omit_first_arg);
        if (!$this->registered_else) {
            $this->raw("else", "} else {\n");
            $this->registered_else = true;
        }
        $this->raw("end{$name}", "}\n");
    }
}
