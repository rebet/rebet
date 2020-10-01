<?php
namespace Rebet\Tools\Template;

/**
 * Renderable Interface
 *
 * This interface is a bridge between Routing and View package.
 * If a class implements Renderable, It means the class can be respond by Router.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Renderable
{
    /**
     * Render the contents
     *
     * @return string
     */
    public function render() : string ;
}
