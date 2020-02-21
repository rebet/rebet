<?php
namespace Rebet\View\Engine;

/**
 * View Template Engine Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Engine
{
    /**
     * Get core object of template engine.
     *
     * @return mixed
     */
    public function core();

    /**
     * Get template paths
     *
     * @return array
     */
    public function getPaths() : array;

    /**
     * Prepend template path.
     *
     * @param string $path
     * @return Engine
     */
    public function prependPath(string $path) : Engine;

    /**
     * Append template path.
     *
     * @param string $path
     * @return Engine
     */
    public function appendPath(string $path) : Engine;

    /**
     * Get the string contents of the view.
     *
     * @param string $name Template name without base template dir and template file suffix
     * @param array $data
     * @return string
     */
    public function render(string $name, array $data = []) : string;

    /**
     * It checks the given name view template exists.
     *
     * @param string $name
     * @return boolean
     */
    public function exists(string $name) : bool;
}
