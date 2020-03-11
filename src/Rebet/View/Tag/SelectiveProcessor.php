<?php
namespace Rebet\View\Tag;

use Rebet\Common\Reflector;

/**
 * Selective Processor Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SelectiveProcessor implements Processor
{
    /**
     * Processor closure selector.
     *
     * @var \Closure
     */
    protected $selector;

    /**
     * Use type convert or not
     *
     * @var bool
     */
    protected $type_convert;

    /**
     * Create Selective Callback Processor.
     *
     * @param \Closure $selector that return appropriate callback closure: function(array $args) : \Closure
     * @param boolean $type_convert
     */
    public function __construct(\Closure $selector, bool $type_convert = true)
    {
        $this->selector     = $selector;
        $this->type_convert = $type_convert;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $args)
    {
        $selector = $this->selector;
        return Reflector::evaluate($selector($args), $args, $this->type_convert);
    }
}
