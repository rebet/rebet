<?php
namespace Rebet\View\Engine\Twig\Parser;

use Rebet\View\Engine\Twig\Node\CodeNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

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
    protected $conjunction;
    protected $open;
    protected $callback;
    protected $close;
    protected $context_args;

    /**
     * Create Code Token Parser
     *
     * @param string $tag
     * @param string|null $conjunction
     * @param string $open
     * @param callable $callback
     * @param string $close
     * @param array $binds (default: [])
     */
    public function __construct(string $tag, ?string $conjunction, string $open, callable $callback, string $close, array $binds = [])
    {
        $this->tag         = $tag;
        $this->conjunction = $conjunction;
        $this->open        = $open;
        $this->close       = $close;
        $this->binds       = $binds;

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
        if (!empty($this->conjunction)) {
            switch ($stream->getCurrent()->getValue()) {
                case $this->conjunction:
                    $stream->expect($stream->getCurrent()->getType(), $this->conjunction);
                    if ($stream->nextIf(Token::NAME_TYPE, 'not')) {
                        $invert = true;
                    }
                    break;
                case "{$this->conjunction} not":
                    $stream->expect($stream->getCurrent()->getType(), "{$this->conjunction} not");
                    $invert = true;
                    break;
                case "not {$this->conjunction}":
                    $stream->expect($stream->getCurrent()->getType(), "not {$this->conjunction}");
                    $invert = true;
                    break;
            }
        }
        $template_args = [];
        while (true) {
            if ($stream->test(Token::BLOCK_END_TYPE)) {
                break;
            }
            if ($stream->nextIf(Token::PUNCTUATION_TYPE, ',')) {
                continue;
            }
            if ($stream->nextIf(Token::OPERATOR_TYPE, 'and')) {
                continue;
            }
            if ($stream->nextIf(Token::OPERATOR_TYPE, 'or')) {
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
     * {@inheritDoc}
     */
    public function getTag()
    {
        return $this->tag;
    }
}
