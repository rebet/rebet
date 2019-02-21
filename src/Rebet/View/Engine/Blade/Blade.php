<?php
namespace Rebet\View\Engine\Blade;

use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade as LaravelBlade;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Rebet\Config\Configurable;
use Rebet\View\Engine\Blade\Compiler\BladeCompiler;
use Rebet\View\Engine\Engine;

/**
 * Blade Templeate Engine Class
 *
 * This class depends on illuminate/view ^5.7, so you should run composer command like below.
 * $ composer require illuminate/view
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Blade implements Engine
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'view_path'   => [],
            'cache_path'  => null,
            'customizers' => [],
        ];
    }
    
    /**
     * The blade template engine factory
     *
     * @var Illuminate\View\Factory
     */
    protected $factory = null;

    /**
     * Create Blade template engine
     */
    public function __construct(array $config = [])
    {
        $view_path   = $config['view_path'] ?? static::config('view_path') ;
        $cache_path  = $config['cache_path'] ?? static::config('cache_path', false) ;
        $customizers = array_merge($config['customizers'] ?? [], static::config('customizers', false, [])) ;

        $resolver   = new EngineResolver();
        $finder     = new FileViewFinder(new Filesystem(), (array)$view_path);
        $dispatcher = new Dispatcher();

        $resolver->register("blade", function () use ($cache_path) {
            if (! is_dir($cache_path)) {
                mkdir($cache_path, 0777, true);
            }
            $blade = new BladeCompiler(new Filesystem(), $cache_path);
            return new CompilerEngine($blade);
        });

        $this->factory = new Factory($resolver, $finder, $dispatcher);

        $app         = LaravelBlade::getFacadeApplication();
        $app['view'] = $this->factory;
        LaravelBlade::setFacadeApplication($app);

        foreach (array_reverse($customizers) as $customizer) {
            $invoker = \Closure::fromCallable($customizer);
            $invoker($this->compiler());
        }
    }

    /**
     * Shortcut for getting BladeCompiler
     *
     * @return Illuminate\View\Compilers\BladeCompiler
     */
    public function compiler() : BladeCompiler
    {
        return $this->factory->getEngineResolver()->resolve('blade')->getCompiler();
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $name, array $data = []) : string
    {
        return $this->factory->make($name, $data)->render();
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $name) : bool
    {
        return $this->factory->exists($name);
    }
}
