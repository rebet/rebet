<?php
namespace Rebet\View\Engine\Blade;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
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
     * Illuminate Container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app = null;

    /**
     * Create Blade template engine
     */
    public function __construct(array $config = [])
    {
        $view_path   = $config['view_path'] ?? static::config('view_path') ;
        $cache_path  = $config['cache_path'] ?? static::config('cache_path', false) ;
        $customizers = array_merge($config['customizers'] ?? [], static::config('customizers', false, [])) ;

        $this->app = new Container();
        $this->app->bind('files', function () {
            return new Filesystem();
        });
        $this->app->bind('view.finder', function ($app) use ($view_path) {
            return new FileViewFinder($app['files'], (array)$view_path);
        });
        $this->app->bind('events', function () {
            return new Dispatcher();
        });
        $this->app->singleton('view.engine.resolver', function ($app) use ($cache_path) {
            if (! is_dir($cache_path)) {
                mkdir($cache_path, 0777, true);
            }
            $resolver = new EngineResolver();
            $app->singleton('blade.compiler', function ($app) use ($cache_path) {
                return new BladeCompiler($app['files'], $cache_path);
            });
            $resolver->register('blade', function () use ($app) {
                return new CompilerEngine($app['blade.compiler']);
            });
            $resolver->register('php', function () {
                return new PhpEngine();
            });
            $resolver->register('file', function () {
                return new FileEngine();
            });
            return $resolver;
        });
        $this->app->singleton('view', function ($app) {
            $env = new Factory($app['view.engine.resolver'], $app['view.finder'], $app['events']);
            $env->setContainer($app);
            $env->share('app', $app);
            return $env;
        });
        Facade::setFacadeApplication($this->app);

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
        return $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $name, array $data = []) : string
    {
        return $this->app['view']->make($name, $data)->render();
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $name) : bool
    {
        return $this->app['view']->exists($name);
    }
}
