<?php
namespace Rebet\View\Tag;

use Rebet\Tools\Reflector;

/**
 * Callback Processor Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class CallbackProcessor implements Processor
{
    /**
     * Processor closure.
     *
     * @var \Closure
     */
    protected $callback;

    /**
     * Use type convert or not
     *
     * @var bool
     */
    protected $type_convert;

    /**
     * Create processor that execute given callback.
     *
     * @param \Closure $callback
     * @param boolean $type_convert
     */
    public function __construct(\Closure $callback, bool $type_convert = true)
    {
        $this->callback     = $callback;
        $this->type_convert = $type_convert;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $args)
    {
        return Reflector::evaluate($this->callback, $args, $this->type_convert);
    }
}
