<?php
namespace Rebet\View\Engine\Smarty;

use Rebet\Config\Configurable;
use Rebet\View\Engine\Engine;
use Illuminate\View\Factory;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\FileViewFinder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Events\Dispatcher;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;

/**
 * Blade templeate engine Class
 *
 * This class depends on illuminate/view ^5.7, so you should run composer command like below.
 * $ composer require illuminate/view
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Blade extends Factory implements Engine
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'view_path'   => [],
            'cache_path'  => null,
            'directives'  => [],
            'ifs'         => [],
            'file_suffix' => '.blade.php',
        ];
    }

    /**
     * Template file suffix
     *
     * @var string
     */
    protected $file_suffix = null;

    /**
     * Create Blade template engine
     */
    public function __construct(array $config = [])
    {
        $this->file_suffix = $config['file_suffix']  ?? static::config('file_suffix') ;
        $view_path         = (array)($config['view_path'] ?? static::config('view_path')) ;
        $cache_path        = $config['cache_path'] ?? static::config('cache_path', false) ;
        $directives        = $config['directives'] ?? static::config('directives', false, []) ;

        $resolver   = new EngineResolver();
        $finder     = new FileViewFinder(new Filesystem(), $view_path);
        $dispatcher = new Dispatcher();

        $resolver->register("blade", function () use ($cache_path) {
            if (! is_dir($cache_path)) {
                mkdir($cache_path, 0777, true);
            }
            $blade = new BladeCompiler(new Filesystem(), $cache_path);
            return new CompilerEngine($blade);
        });

        parent::__construct($resolver, $finder, $dispatcher);

        foreach ($directives as [$register, $directive, $compiler]) {
            $this->compiler()->$register($directive, $compiler);
        }
    }

    /**
     * Shortcut for getting BladeCompiler
     *
     * @return Illuminate\View\Compilers\BladeCompiler
     */
    public function compiler() : BladeCompiler
    {
        return $this->getEngineResolver()->resolve('blade')->getCompiler();
    }

    /**
     * Get the string contents of the view.
     *
     * @param string $name Template name without base template dir and template file suffix
     * @param array $data
     * @return string
     */
    public function render(string $name, array $data) : string
    {
        return $this->make($name.$this->file_suffix, $data)->render();
    }
}
