<?php
namespace Rebet\View\Engine\Twig\Node;

use Twig\Compiler;
use Twig\Node\Node;

/**
 * RawNode Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RawNode extends Node
{
    /**
     * Create Raw Node
     *
     * @param string $code
     * @param int $lineno (default: 0)
     * @param string|null $tag (default: null)
     */
    public function __construct(string $code, int $lineno = 0, ?string $tag = null)
    {
        parent::__construct([], ['code'  => $code ], $lineno, $tag);
    }

    /**
     * {@inheritDoc}
     */
    public function compile(Compiler $compiler)
    {
        $compiler->raw($this->getAttribute('code'));
    }
}
