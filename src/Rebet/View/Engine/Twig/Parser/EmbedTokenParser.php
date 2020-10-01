<?php
namespace Rebet\View\Engine\Twig\Parser;

use Rebet\Tools\Arrays;
use Rebet\Translation\Translator;
use Rebet\View\Engine\Twig\Node\EmbedNode;
use Rebet\View\Tag\Processor;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\NameExpression;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Embed Token Parser Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class EmbedTokenParser extends AbstractTokenParser
{
    /**
     * Variadic arguments separators key.
     *
     * @var string
     */
    private const VARIADIC = '...';

    /**
     * Tag name
     *
     * @var string
     */
    protected $tag;

    /**
     * Verb phrase if the tag has verbs.
     *
     * @var string|null
     */
    protected $verbs;

    /**
     * Partial code that opens like '', 'if(', 'echo(', '$foo = '.
     * This partial code handle return value of main process callback function.
     *
     * @var string
     */
    protected $open;

    /**
     * Partial code that closes like '', ') {', ');', ';'.
     * This partial code handle return value of main process callback function.
     *
     * @var string
     */
    protected $close;

    /**
     * The arguments separators.
     * [
     *     'with',               // Ordered 1st separator.
     *     ['then', '?'],        // Ordered 2nd separator can be 'then' or '?'.
     *     ['else', ':'],        // Ordered 3rd separator can be 'else' or ':'.
     *     '...' => ',',         // Variadic separator can be repeated by ','.
     *     '...' => [',', 'or'], // Variadic separator can be repeated by ',' or 'or'.
     * ]
     *
     * @var array
     */
    protected $separators;

    /**
     * The code tag support to omit first argument by special outer tag that defined it.
     *
     * @var bool
     */
    protected $can_omit_first_arg;

    /**
     * Create Code Token Parser.
     *
     * @param string $tag
     * @param string|null $verbs
     * @param array|null $separators null for no argument, [] for one argument
     * @param string $open
     * @param Processor $processor
     * @param string $close
     * @param array $binds (default: [])
     * @param bool $can_omit_first_arg (default: false)
     */
    public function __construct(string $tag, ?string $verbs, ?array $separators, string $open, Processor $processor, string $close, array $binds = [], bool $can_omit_first_arg = false)
    {
        $this->tag                = $tag;
        $this->verbs              = $verbs;
        $this->separators         = $separators;
        $this->open               = $open;
        $this->close              = $close;
        $this->binds              = $binds;
        $this->can_omit_first_arg = $can_omit_first_arg;

        EmbedNode::addCode($tag, $processor);
    }

    /**
     * {@inheritDoc}
     */
    public function parse(Token $token)
    {
        $stream = $this->parser->getStream();
        $invert = false;
        if ($stream->nextIf(Token::OPERATOR_TYPE, 'not')) {
            $invert = true;
        }
        $token = $stream->getCurrent();
        if (!empty($this->verbs)) {
            switch ($token->getValue()) {
                case $this->verbs:
                    $stream->expect($token->getType(), $this->verbs);
                    if ($stream->nextIf(Token::NAME_TYPE, 'not')) {
                        $invert = true;
                    }
                    break;
                case "{$this->verbs} not":
                    $stream->expect($token->getType(), "{$this->verbs} not");
                    $invert = true;
                    break;
                case "not {$this->verbs}":
                    $stream->expect($token->getType(), "not {$this->verbs}");
                    $invert = true;
                    break;
            }
        }

        $separators = $this->separators;
        if ($this->can_omit_first_arg && in_array($token->getValue(), Arrays::toArray($separators[0] ?? []))) {
            $stream->expect($token->getType(), Arrays::remove($separators, 0));
            $separators = array_merge($separators);
        }

        return new EmbedNode($this->open, $this->tag, $this->parseArguments($separators), $this->close, $this->binds, $invert, $token->getLine());
    }

    /**
     * Parses arguments.
     *
     * @param bool $allow_arrow Whether to allow arrow function call
     * @return array
     * @throws SyntaxError
     */
    public function parseArguments(?array $separators, bool $allow_arrow = false) : array
    {
        $args     = [];
        $stream   = $this->parser->getStream();
        $i        = 0;
        $variadic = Arrays::remove($separators, static::VARIADIC);
        while (!$stream->test(Token::BLOCK_END_TYPE)) {
            $precedence = PHP_INT_MAX; // @todo

            if ($this->separators === null) {
                throw new SyntaxError(
                    "Too many code arguments. The code tag '{$this->tag}' takes no arguments.",
                    $this->parser->getCurrentToken()->getLine(),
                    $stream->getSourceContext()
                );
            }

            if (!empty($args)) {
                if ($this->separators === []) {
                    throw new SyntaxError(
                        "Too many code arguments. The code tag '{$this->tag}' takes only one argument.",
                        $this->parser->getCurrentToken()->getLine(),
                        $stream->getSourceContext()
                    );
                }

                $candidates = Arrays::toArray($separators[$i++] ?? $variadic);
                if ($candidates === null) {
                    throw new SyntaxError(
                        "Too many code arguments. The code tag '{$this->tag}' takes up to ".(count($separators) + 1)." arguments.",
                        $this->parser->getCurrentToken()->getLine(),
                        $stream->getSourceContext()
                    );
                }

                $token_type        = Token::NAME_TYPE;
                $allow_empty       = false;
                $separator_matched = false;
                foreach ($candidates as $separator) {
                    if ($separator === '') {
                        $allow_empty = true;
                        continue;
                    }
                    foreach ([Token::PUNCTUATION_TYPE, Token::NAME_TYPE, Token::OPERATOR_TYPE] as $token_type) {
                        if ($stream->test($token_type, $separator)) {
                            $separator_matched = true;
                            break 2;
                        }
                    }
                }
                if ($separator_matched) {
                    $stream->next();
                } elseif (!$allow_empty) {
                    throw new SyntaxError(
                        Translator::ordinalize($i, 'en')." and ".Translator::ordinalize($i + 1, 'en')." arguments of the code tag '{$this->tag}' must be separated by '".implode("' or '", $candidates)."'.",
                        $this->parser->getCurrentToken()->getLine(),
                        $stream->getSourceContext()
                    );
                }
            }

            $name  = null;
            $value = $this->parser->getExpressionParser()->parseExpression($precedence, $allow_arrow);
            if ($token = $stream->nextIf(Token::OPERATOR_TYPE, '=')) {
                if (!$value instanceof NameExpression) {
                    throw new SyntaxError(
                        sprintf('A parameter name must be a string, "%s" given.', \get_class($value)),
                        $token->getLine(),
                        $stream->getSourceContext()
                    );
                }
                $name  = $value->getAttribute('name');
                $value = $this->parser->getExpressionParser()->parseExpression($precedence, $allow_arrow);
            }

            if (null === $name) {
                $args[] = $value;
            } else {
                $args[$name] = $value;
            }
        }

        $stream->expect(Token::BLOCK_END_TYPE);
        return $args;
    }

    /**
     * {@inheritDoc}
     */
    public function getTag()
    {
        return $this->tag;
    }
}
