<?php
namespace Rebet\View\Engine\Blade;

use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Utility\OverrideOption;
use Rebet\Tools\Utility\Path;
use Rebet\View\Engine\Blade\Compiler\BladeCompiler;
use Rebet\View\Engine\Blade\Support\Application;
use Rebet\View\Engine\Engine;

/**
 * Blade Templeate Engine Class
 *
 * This class depends on illuminate/view ^10.0, so you should run composer command like below.
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

    public static function defaultConfigOverrideOptions() : array
    {
        return [
            'customizers' => OverrideOption::APPEND,
        ];
    }

    /**
     * Crear view template engine.
     *
     * @return void
     */
    public static function clear() : void
    {
        Application::setInstance(null);
    }

    /**
     * Create Blade template engine.
     * It provides the Blade engine components to the globally container if 'view' component not exists.
     *
     * @param boolean $clean_rebuild (default: false)
     */
    public function __construct(bool $clean_rebuild = false)
    {
        $app = Application::getInstance() ;
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
            $resolver->register('php', function () use ($app) {
                return new PhpEngine($app['files']);
            });
            $resolver->register('file', function () use ($app) {
                return new FileEngine($app['files']);
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

        foreach (static::config('customizers', false, []) as $customizer) {
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
        return Application::getInstance()['view'];
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
        $finder = $this->core()->getFinder();
        if (!$finder instanceof FileViewFinder) {
            throw new LogicException('The resolved view finder is not a FileViewFinder.');
        }
        return $finder;
    }

    /**
     * Shortcut for getting BladeCompiler
     *
     * @return BladeCompiler
     */
    public function compiler() : BladeCompiler
    {
        $engine = $this->core()->getEngineResolver()->resolve('blade');
        if (!$engine instanceof CompilerEngine) {
            throw new LogicException('The resolved "blade" engine is not a CompilerEngine.');
        }
        $compiler = $engine->getCompiler();
        if (!$compiler instanceof BladeCompiler) {
            throw new LogicException('The resolved compiler is not a BladeCompiler.');
        }
        return $compiler;
    }
}
