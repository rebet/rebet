<?php
namespace Rebet\View\Tag;

/**
 * Processor Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Processor
{
    /**
     * Execute processor with given args.
     *
     * @param array $args
     * @return mixed
     */
    public function execute(array $args);
}
