<?php
namespace Rebet\View\Engine\Blade;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Rebet\Tools\Path;
use Rebet\Tools\Config\Configurable;
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
     * Crear view template engine.
     *
     * @return void
     */
    public static function clear() : void
    {
        Container::setInstance(null);
    }

    /**
     * Create Blade template engine.
     * It provides the Blade engine components to the globally container if 'view' component not exists.
     *
     * @param boolean $clean_rebuild (default: false)
     */
    public function __construct(bool $clean_rebuild = false)
    {
        $app = Container::getInstance() ;
        if ($app->has('view') && !$clean_rebuild) {
            return;
        }

        $view_path  = (array)static::config('view_path');
        $cache_path = static::config('cache_path', false);

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
            call_user_func($customizer, $this);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return Factory
     */
    public function core()
    {
        return Container::getInstance()['view'];
    }

    /**
     * {@inheritDoc}
     */
    public function getPaths() : array
    {
        return array_map(function ($path) { return Path::normalize($path); }, $this->finder()->getPaths());
    }

    /**
     * {@inheritDoc}
     */
    public function prependPath(string $path) : Engine
    {
        $path = Path::normalize($path);
        if (!in_array($path, $this->getPaths())) {
            $this->finder()->prependLocation($path);
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
            $this->finder()->addLocation($path);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $name, array $data = []) : string
    {
        return $this->core()->make($name, $data)->render();
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $name) : bool
    {
        return $this->core()->exists($name);
    }

    /**
     * Shortcut for getting ViewFinder
     *
     * @return FileViewFinder
     */
    public function finder() : FileViewFinder
    {
        return $this->core()->getFinder();
    }

    /**
     * Shortcut for getting BladeCompiler
     *
     * @return BladeCompiler
     */
    public function compiler() : BladeCompiler
    {
        return $this->core()->getEngineResolver()->resolve('blade')->getCompiler();
    }
}
