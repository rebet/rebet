<?php
namespace Rebet\View\Engine\Twig;

use Rebet\Common\Path;
use Rebet\Config\Configurable;
use Rebet\View\Engine\Engine;
use Rebet\View\Engine\Twig\Environment\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Twig templeate engine Class
 *
 * This class depends on twig/twig ^2.5, so you should run composer command like below.
 * $ composer require twig/twig
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Twig implements Engine
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'template_dir' => [],
            'options'      => [],
            'customizers'  => [],
            'file_suffix'  => '.twig',
        ];
    }

    /**
     * Real twig template object.
     *
     * @var Environment
     */
    protected static $twig = null;

    /**
     * Template file suffix
     *
     * @var string
     */
    protected $file_suffix = null;

    /**
     * Crear view template engine.
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$twig = null;
    }

    /**
     * Create Twig template engine
     *
     * @param bool $clean_rebuild (default: false)
     */
    public function __construct(bool $clean_rebuild = false)
    {
        $this->file_suffix = static::config('file_suffix') ;

        if (static::$twig === null || $clean_rebuild) {
            $template_dir      = static::config('template_dir') ;
            $options           = static::config('options', false, []) ;
            static::$twig      = new Environment(new FilesystemLoader($template_dir), $options);
            foreach (array_reverse(static::config('customizers', false, [])) as $customizer) {
                call_user_func($customizer, $this);
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return Environment
     */
    public function core()
    {
        return static::$twig;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaths() : array
    {
        return static::$twig->getLoader()->getPaths();
    }

    /**
     * {@inheritDoc}
     */
    public function prependPath(string $path) : Engine
    {
        $path = Path::normalize($path);
        if (!in_array($path, $this->getPaths())) {
            static::$twig->getLoader()->prependPath($path);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function appendPath(string $path) : Engine
    {
        $path = Path::normalize($path);
        if (!in_array($path, $this->getPaths())) {
            static::$twig->getLoader()->addPath($path);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $name, array $data = []) : string
    {
        return static::$twig->render($name.$this->file_suffix, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $name) : bool
    {
        return static::$twig->getLoader()->exists($name.$this->file_suffix);
    }
}
