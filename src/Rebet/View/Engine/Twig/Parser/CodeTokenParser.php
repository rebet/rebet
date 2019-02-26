<?php
namespace Rebet\View\Engine\Twig\Parser;

use Rebet\Common\Strings;
use Rebet\View\Engine\Twig\Node\CodeNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

/**
 * CodeTokenParser Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class CodeTokenParser extends AbstractTokenParser
{
    protected $tag;
    protected $verbs;
    protected $open;
    protected $callback;
    protected $close;
    protected $separators;

    /**
     * Create Code Token Parser.
     * The separators defined by array like below,
     *
     *  1) Separators array such as ['/a/', '/b/', '/c/'] expects separator tokens to exist in this order.
     *     Note: Not guarantee that all of the defined separators will exist.
     *  2) Separators array items are regex.
     *  3) If the separator item ends with '*' like '/,/*' that means item '/,/' can be none or repeated.
     *
     * @param string $tag
     * @param string|null $verbs
     * @param array $separators
     * @param string $open
     * @param callable $callback
     * @param string $close
     * @param array $binds (default: [])
     */
    public function __construct(string $tag, ?string $verbs, array $separators, string $open, callable $callback, string $close, array $binds = [])
    {
        $this->tag        = $tag;
        $this->verbs      = $verbs;
        $this->separators = $separators;
        $this->open       = $open;
        $this->close      = $close;
        $this->binds      = $binds;

        CodeNode::addCallback($tag, $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function parse(\Twig_Token $token)
    {
        $stream = $this->parser->getStream();
        $invert = false;
        if ($stream->nextIf(Token::OPERATOR_TYPE, 'not')) {
            $invert = true;
        }
        if (!empty($this->verbs)) {
            switch ($stream->getCurrent()->getValue()) {
                case $this->verbs:
                    $stream->expect($stream->getCurrent()->getType(), $this->verbs);
                    if ($stream->nextIf(Token::NAME_TYPE, 'not')) {
                        $invert = true;
                    }
                    break;
                case "{$this->verbs} not":
                    $stream->expect($stream->getCurrent()->getType(), "{$this->verbs} not");
                    $invert = true;
                    break;
                case "not {$this->verbs}":
                    $stream->expect($stream->getCurrent()->getType(), "not {$this->verbs}");
                    $invert = true;
                    break;
            }
        }
        $template_args = [];
        $separators    = $this->separators;
        while (true) {
            if ($stream->test(Token::BLOCK_END_TYPE)) {
                break;
            }
            if ($this->skipSeparatorsToken($stream, $separators)) {
                continue;
            }
            if ($stream->test(Token::PUNCTUATION_TYPE, '[')) {
                $template_args[] = $this->parser->getExpressionParser()->parseArrayExpression();
                continue;
            }
            $template_args[] = $this->parser->getExpressionParser()->parsePrimaryExpression();
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        return new CodeNode($this->open, $this->tag, new Node($template_args), $this->close, $this->binds, $invert, $token->getLine());
    }

    /**
     * Skip separator tokens.
     * The separators defined by array like below,
     *
     *  1) Separators array such as ['/a/', '/b/', '/c/'] expects separator tokens to exist in this order.
     *     Note: Not guarantee that all of the defined separators will exist.
     *  2) Separators array items are regex.
     *  3) If the separator item ends with '*' like '/,/*' that means item '/,/' can be none or repeated.
     *
     * @param TokenStream $stream
     * @param array $separators
     * @return bool
     */
    private function skipSeparatorsToken(TokenStream $stream, array &$separators) : bool
    {
        if (empty($separators)) {
            return false;
        }

        $repeatable = false;
        $current    = current($separators);
        if (Strings::endsWith($current, '*')) {
            $current    = Strings::rtrim($current, '*', 1);
            $repeatable = true;
        }

        $token = $stream->getCurrent()->getValue();
        if (preg_match($current, $token)) {
            if (!$repeatable) {
                array_shift($separators);
            }
            $stream->next();
            return true;
        }

        if ($repeatable) {
            next($separators);
            if ($this->skipSeparatorsToken($stream, $separators)) {
                array_shift($separators);
                return true;
            }
        }

        reset($separators);
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getTag()
    {
        return $this->tag;
    }
}
