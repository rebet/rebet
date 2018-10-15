<?php
namespace Rebet\View;

use Rebet\Bridge\Renderable;
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
     * View name
     *
     * @var string
     */
    public $name = null;

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
     * @return self
     */
    public static function of(string $name) : self
    {
        return new static($name);
    }

    /**
     * Create a view
     *
     * @param string $name
     * @param Engine|null $engine
     */
    public function __construct(string $name, ?Engine $engine = null)
    {
        $this->name   = $name;
        $this->engine = $engine ?? static::configInstantiate('engine');
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
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    /**
     * Add validation errors to the view.
     *
     * @param array|null $errors
     * @return self
     */
    public function errors(?array $errors) : self
    {
        if ($errors) {
            $this->with('errors', $errors);
        }
        return $this;
    }

    /**
     * Get the string contents of the view.
     *
     * @return string
     */
    public function render() : string
    {
        return $this->engine->render($this->name, $this->data);
    }
}
