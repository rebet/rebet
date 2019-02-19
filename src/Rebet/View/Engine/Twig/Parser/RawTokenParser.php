<?php
namespace Rebet\View\Engine\Twig\Parser;

use Rebet\View\Engine\Twig\Node\RawNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * RawTokenParser Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RawTokenParser extends AbstractTokenParser
{
    protected $tag;
    protected $code;

    /**
     * Create Code Token Parser
     *
     * @param string $tag
     * @param string $code
     */
    public function __construct(string $tag, string $code)
    {
        $this->tag  = $tag;
        $this->code = $code;
    }

    /**
     * {@inheritDoc}
     */
    public function parse(\Twig_Token $token)
    {
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        return new RawNode($this->code, $token->getLine(), $this->tag);
    }

    /**
     * {@inheritDoc}
     */
    public function getTag()
    {
        return $this->tag;
    }
}
