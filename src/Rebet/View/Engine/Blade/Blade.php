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
     * Clear Blade view container.
     *
     * @return void
     */
    public static function clear() : void
    {
        Container::setInstance(new Container()) ;
    }

    /**
     * It provides the Blade engine components to the globally container if 'view' component not exists.
     *
     * @param array $view_path
     * @param string $cache_path
     * @param bool $clean_rebuild (default: false)
     * @return Container
     */
    public static function provide(array $view_path, string $cache_path, bool $clean_rebuild = false) : void
    {
        if ($clean_rebuild) {
            $app = Container::setInstance(new Container()) ;
        } else {
            $app = Container::getInstance() ?? Container::setInstance(new Container()) ;
            if ($app->has('view')) {
                return;
            }
        }

        $app->bind('files', function () {
            return new Filesystem();
        });
        $app->bind('view.finder', function ($app) use ($view_path) {
            return new FileViewFinder($app['files'], (array)$view_path);
        });
        $app->bind('events', function () {
            return new Dispatcher();
        });
        $app->singleton('view.engine.resolver', function ($app) use ($cache_path) {
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
        $app->singleton('view', function ($app) {
            $env = new Factory($app['view.engine.resolver'], $app['view.finder'], $app['events']);
            $env->setContainer($app);
            $env->share('app', $app);
            return $env;
        });
        Facade::setFacadeApplication($app);

        foreach (array_reverse(static::config('customizers', false, [])) as $customizer) {
            call_user_func($customizer, static::compiler());
        }
    }

    /**
     * Create Blade template engine
     *
     * @param array $config
     * @param boolean $clean_rebuild (default: false)
     */
    public function __construct(array $config = [], bool $clean_rebuild = false)
    {
        $view_path   = $config['view_path'] ?? static::config('view_path') ;
        $cache_path  = $config['cache_path'] ?? static::config('cache_path', false) ;

        static::provide((array)$view_path, $cache_path, $clean_rebuild);
    }

    /**
     * Get the view factory instance.
     *
     * @return Factory
     */
    protected static function factory() : Factory
    {
        return Container::getInstance()['view'];
    }

    /**
     * Shortcut for getting BladeCompiler
     *
     * @return Illuminate\View\Compilers\BladeCompiler
     */
    public static function compiler() : BladeCompiler
    {
        return static::factory()->getEngineResolver()->resolve('blade')->getCompiler();
    }

    /**
     * Customize Blade template engine by given callback customizer.
     *
     * @param callable $customizer is function(BladeCompiler $blade) : void
     * @return void
     */
    public static function customize(callable $customizer) : void
    {
        call_user_func($customizer, static::compiler());
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $name, array $data = []) : string
    {
        return static::factory()->make($name, $data)->render();
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $name) : bool
    {
        return static::factory()->exists($name);
    }
}
