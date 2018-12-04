<?php
namespace Rebet\View;

use Rebet\Common\Renderable;
use Rebet\Config\Configurable;
use Rebet\View\Engine\Engine;

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
            'engine' => null,
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
    public $engine = null;

    /**
     * View data.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Create a view of given name.
     *
     * @param string $name
     * @param callable|null $changer function($name){...} to return cahnged name.
     * @return self
     */
    public static function of(string $name, ?callable $changer = null) : self
    {
        return new static($name, $changer);
    }

    /**
     * Create a view
     *
     * @param string $name
     * @param callable|null $changer function($name){...} to return cahnged view name.
     * @param Engine|null $engine
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
     * @param callable $composer
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
                return $value instanceof FilterableValue ? $value : FilterableValue::of($value) ;
            }, $key));
        } else {
            $this->data[$key] = $value instanceof FilterableValue ? $value : FilterableValue::of($value);
        }
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
        $changer = $this->changer;
        $name    = $changer ? $changer($this->name) : $this->name;
        try {
            return $this->engine->render($name, $this->data);
        } catch (\Throwable $e) {
            throw new ViewRenderFailedException("The view {$name} render failed because of exception occurred.", null, $e);
        }
    }
}
