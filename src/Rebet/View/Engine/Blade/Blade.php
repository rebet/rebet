<?php
namespace Rebet\View\Engine\Blade;

use Rebet\Config\Configurable;
use Rebet\View\Engine\Engine;
use Illuminate\View\Factory;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\FileViewFinder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Events\Dispatcher;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\Support\Facades\Blade as LaravelBlade;

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
            'custom'      => [
                'directive' => [],
                'if'        => [],
                'component' => [],
                'include'   => [],
            ],
        ];
    }
    
    /**
     * Allow methods for register custom directives.
     *
     * @var array
     */
    private const ALLOW_DIRECTIVE_METHODS = ['directive', 'if', 'component', 'include'];

    /**
     * Create Blade template engine
     */
    public function __construct(array $config = [])
    {
        $view_path  = $config['view_path']  ?? static::config('view_path') ;
        $cache_path = $config['cache_path'] ?? static::config('cache_path', false) ;
        $custom     = $config['custom']     ?? static::config('custom', false, []) ;

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

        parent::__construct($resolver, $finder, $dispatcher);
        $app = LaravelBlade::getFacadeApplication();
        $app['view'] = $this;
        LaravelBlade::setFacadeApplication($app);

        foreach ($custom as $register => $directives) {
            if (empty($directives) || !in_array($register, static::ALLOW_DIRECTIVE_METHODS)) {
                continue;
            }
            foreach (array_reverse($directives) as $directive) {
                if (empty($directive)) {
                    continue;
                }
                if (is_array($directive)) {
                    $this->compiler()->$register(...$directive);
                    continue;
                }
                if (is_iterable($directive)) {
                    foreach ($directive as $item) {
                        $this->compiler()->$register(...$item);
                    }
                    continue;
                }
                throw new \LogicException("Invalid Blade custom configure in custom.{$register}.");
            }
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
    public function render(string $name, array $data = []) : string
    {
        return $this->make($name, $data)->render();
    }
}
