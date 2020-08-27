<?php
namespace Rebet\View;

use Rebet\Common\Renderable;
use Rebet\Config\Configurable;
use Rebet\Stream\Stream;
use Rebet\View\Engine\Engine;
use Rebet\View\Exception\ViewRenderFailedException;

/**
 * View Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class View implements Renderable
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'engine'        => null,
            'eof_line_feed' => EofLineFeed::TRIM()
        ];
    }

    /**
     * The global share valiables.
     *
     * @var array
     */
    protected static $share = [];

    /**
     * The view valiable composers.
     *
     * @var array
     */
    protected static $composer = [];

    /**
     * View name
     *
     * @var string
     */
    public $name = null;

    /**
     * View changer
     *
     * @var \Closure
     */
    protected $changer = null;

    /**
     * View template engine
     *
     * @var Engine
     */
    protected $engine = null;

    /**
     * View data.
     *
     * @var array
     */
    protected $data = [];

    /**
     * EOF line feed processing.
     *
     * @var EofLineFeed
     */
    protected $eof = null;

    /**
     * Check view configuration is enabled or not.
     *
     * @return bool
     */
    public static function isEnabled() : bool
    {
        return static::config('engine', false) !== null;
    }

    /**
     * Create a view of given name.
     *
     * @param string $name
     * @param callable|null $changer function($view_name):string to return cahnged name.
     * @return self
     */
    public static function of(string $name, ?callable $changer = null) : self
    {
        return new static($name, $changer);
    }

    /**
     * Clear the global share valiables and view valiable composers.
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$share    = [];
        static::$composer = [];
    }

    /**
     * Create a view
     *
     * @param string $name
     * @param callable|null $changer function($view_name):string to return cahnged view name.
     * @param Engine|null $engine (default: null for use configure setting)
     */
    public function __construct(string $name, ?callable $changer = null, ?Engine $engine = null)
    {
        $this->name    = $name;
        $this->changer = $changer;
        $this->engine  = $engine ?? static::configInstantiate('engine');

        $this->with(static::$share);
        foreach (static::$composer as $regex => $composer) {
            if (preg_match($regex, $name)) {
                $composer($this);
            }
        }
    }

    /**
     * Set the view valiable composer.
     *
     * @param string $regex
     * @param callable $composer function(View $view):void
     * @return void
     */
    public static function composer(string $regex, callable $composer) : void
    {
        static::$composer[$regex] = \Closure::fromCallable($composer);
    }

    /**
     * Set the global share valiables.
     *
     * @param string|array $key
     * @param mixed $value
     * @return void
     */
    public static function share($key, $value = null) : void
    {
        if (is_array($key)) {
            static::$share = array_merge(static::$share, $key);
        } else {
            static::$share[$key] = $value;
        }
    }

    /**
     * Get the global shared valiables.
     *
     * @param string $key
     * @return void
     */
    public static function shared(string $key)
    {
        return static::$share[$key] ?? null;
    }

    /**
     * Add a piece of data to the view.
     *
     * @param string|array $key
     * @param mixed $value
     * @return self
     */
    public function with($key, $value = null) : self
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, array_map(function ($value) {
                return Stream::of($value) ;
            }, $key));
        } else {
            $this->data[$key] = Stream::of($value);
        }
        return $this;
    }

    /**
     * Set EOF line feed processer.
     *
     * @param EofLineFeed $processer
     * @return self
     */
    public function eof(EofLineFeed $processer) : self
    {
        $this->eof = $processer;
        return $this;
    }

    /**
     * Get the string contents of the view.
     *
     * @return string
     * @throws ViewRenderFailedException
     */
    public function render() : string
    {
        $names = $this->getPossibleNames();
        $eof   = $this->eof ?? static::config('eof_line_feed');
        foreach ($names as $name) {
            if ($this->engine->exists($name)) {
                try {
                    return $eof->process($this->engine->render($name, $this->data));
                } catch (\Throwable $e) {
                    throw (new ViewRenderFailedException("The view [{$this->name}] (actual: {$name}) render failed because of exception occurred."))->caused($e);
                }
            }
        }

        throw new ViewRenderFailedException("The view [{$this->name}] (possible: ".join(', ', $names).") render failed because of all of view templates not exists.");
    }

    /**
     * It checks the view template exists.
     *
     * @return boolean
     */
    public function exists() : bool
    {
        foreach ($this->getPossibleNames() as $name) {
            if ($this->engine->exists($name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get possible names that provided by changer.
     *
     * @return string[]
     */
    public function getPossibleNames() : array
    {
        $changer = $this->changer;
        return (array)($changer ? $changer($this->name) : $this->name);
    }

    /**
     * Get template paths
     *
     * @return array
     */
    public function getPaths() : array
    {
        return $this->engine->getPaths();
    }

    /**
     * Prepend template path.
     *
     * @param string $path
     * @return self
     */
    public function prependPath(string $path) : self
    {
        $this->engine->prependPath($path);
        return $this;
    }

    /**
     * Append template path.
     *
     * @param string $path
     * @return self
     */
    public function appendPath(string $path) : self
    {
        $this->engine->appendPath($path);
        return $this;
    }
}
