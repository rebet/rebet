<?php
namespace Rebet\View\Engine\Blade\Compiler;

use Illuminate\View\Compilers\BladeCompiler as LaravelBladeCompiler;

/**
 * Blade Compilera Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BladeCompiler extends LaravelBladeCompiler
{
    /**
     * All custom "codes" handlers.
     *
     * @var array
     */
    protected $codes = [];

    /**
     * Register an "code" statement directive.
     * If you give '$errors' as bind then you can get the $errors of assigned value as first argument of callback.
     *
     * @param string $name
     * @param string $open code to callbak returns like 'echo(', '$var =', 'if(', '' etc
     * @param callable $callback
     * @param string $close code to callbak returns like ');', ';', '):' etc
     * @param string $bind (default: null)
     * @return void
     */
    public function code($name, string $open, callable $callback, string $close, string $bind = null) : void
    {
        $this->codes[$name] = $callback;
        $this->directive($name, function ($expression) use ($name, $open, $close, $bind) {
            $expression = empty($expression) ? '' : ", {$expression}" ;
            return $bind
                ? "<?php {$open} \Illuminate\Support\Facades\Blade::call('{$name}', {$bind}{$expression}) {$close} ?>"
                : "<?php {$open} \Illuminate\Support\Facades\Blade::call('{$name}'{$expression}) {$close} ?>"
                ;
        });
    }

    /**
     * Call the codes closuer.
     *
     * @param string $name
     * @param array $parameters
     * @return bool
     */
    public function call($name, ...$parameters)
    {
        return call_user_func($this->codes[$name], ...$parameters);
    }

    /**
     * Register an "if" (and not) statement directive.
     *
     * @param string $name
     * @param callable $callback
     * @return void
     */
    public function if($name, callable $callback)
    {
        parent::if($name, $callback);

        $this->directive($name.'not', function ($expression) use ($name) {
            return $expression !== ''
                    ? "<?php if (! \Illuminate\Support\Facades\Blade::check('{$name}', {$expression})): ?>"
                    : "<?php if (! \Illuminate\Support\Facades\Blade::check('{$name}')): ?>";
        });

        $this->directive('else'.$name.'not', function ($expression) use ($name) {
            return $expression !== ''
                ? "<?php elseif (! \Illuminate\Support\Facades\Blade::check('{$name}', {$expression})): ?>"
                : "<?php elseif (! \Illuminate\Support\Facades\Blade::check('{$name}')): ?>";
        });
    }
}
