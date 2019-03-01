<?php
namespace Rebet\View\Engine\Twig;

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
            'template_dir' => null,
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
     * Create Twig template engine
     *
     * @param array $config
     * @param bool $clean_rebuild (default: false)
     */
    public function __construct(array $config = [], bool $clean_rebuild = false)
    {
        $this->file_suffix = $config['file_suffix'] ?? static::config('file_suffix') ;
        $template_dir      = $config['template_dir'] ?? static::config('template_dir') ;
        $options           = $config['options'] ?? static::config('options', false, []) ;

        if (static::$twig === null || $clean_rebuild) {
            static::$twig = new Environment(new FilesystemLoader($template_dir), $options);
            foreach (array_reverse(static::config('customizers', false, [])) as $customizer) {
                call_user_func($customizer, static::$twig);
            }
        }
    }

    /**
     * Customize Twig template engine by given callback customizer.
     *
     * @param callable $customizer is function(Environment $twig) : void
     * @return void
     */
    public static function customize(callable $customizer) : void
    {
        call_user_func($customizer, static::$twig);
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
