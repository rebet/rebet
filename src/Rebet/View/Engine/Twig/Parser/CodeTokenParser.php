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
    protected $open;
    protected $callback;
    protected $close;
    protected $context_args;

    /**
     * Create Code Token Parser
     *
     * @param string $tag
     * @param string $open
     * @param callable $callback
     * @param string $close
     * @param array $binds (default: [])
     */
    public function __construct(string $tag, string $open, callable $callback, string $close, array $binds = [])
    {
        $this->tag   = $tag;
        $this->open  = $open;
        $this->close = $close;
        $this->binds = $binds;

        CodeNode::addCallback($tag, $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function parse(\Twig_Token $token)
    {
        $stream = $this->parser->getStream();
        if ($stream->nextIf(Token::BLOCK_END_TYPE)) {
            $template_args = new Node([]);
        } else {
            $template_args = $this->parser->getExpressionParser()->parseArguments();
            $stream->expect(Token::BLOCK_END_TYPE);
        }
        return new CodeNode($this->open, $this->tag, $template_args, $this->close, $this->binds, $token->getLine());
    }

    /**
     * {@inheritDoc}
     */
    public function getTag()
    {
        return $this->tag;
    }
}
