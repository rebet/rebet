<?php
namespace Rebet\View\Engine\Blade\Compiler;

use Illuminate\View\Compilers\BladeCompiler as LaravelBladeCompiler;
use Rebet\Common\Exception\LogicException;

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
     * Register an "raw" statement directive.
     *
     * @param string $name
     * @param string $code
     * @return void
     */
    public function raw(string $name, string $code) : void
    {
        $this->directive($name, function () use ($code) {
            return "<?php {$code} ?>";
        });
    }

    /**
     * Register an "code" statement directive.
     * If you give '$errors' as binds then you can get the $errors of assigned value as first argument of callback.
     *
     * @param string $name
     * @param string $open code to callbak returns like 'echo(', '$var =', 'if(', '' etc
     * @param callable $callback
     * @param string $close code to callbak returns like ');', ';', '):' etc
     * @param string $binds (default: null)
     * @return void
     */
    public function code(string $name, string $open, callable $callback, string $close, string $binds = null) : void
    {
        $this->codes[$name] = $callback;
        $this->directive($name, function ($expression) use ($name, $open, $close, $binds) {
            $expression = empty($expression) ? '' : ", {$expression}" ;
            return $binds
                ? "<?php {$open} \Illuminate\Support\Facades\Blade::call('{$name}', {$binds}{$expression}) {$close} ?>"
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
     * @param string|array $name
     * @param callable $callback
     * @param string $binds (default: null)
     * @return void
     */
    public function if($name, callable $callback, string $binds = null)
    {
        $this->conditions[$name] = $callback;

        $this->directive($name, function ($expression) use ($name, $binds) {
            $expression = empty($expression) ? '' : ", {$expression}" ;
            return $binds
                    ? "<?php if (\Illuminate\Support\Facades\Blade::check('{$name}', {$binds}{$expression})): ?>"
                    : "<?php if (\Illuminate\Support\Facades\Blade::check('{$name}'{$expression})): ?>";
        });

        $this->directive('else'.$name, function ($expression) use ($name, $binds) {
            $expression = empty($expression) ? '' : ", {$expression}" ;
            return $binds
                ? "<?php elseif (\Illuminate\Support\Facades\Blade::check('{$name}', {$binds}{$expression})): ?>"
                : "<?php elseif (\Illuminate\Support\Facades\Blade::check('{$name}'{$expression})): ?>";
        });

        $this->directive('end'.$name, function () {
            return '<?php endif; ?>';
        });

        $this->directive(
            $name.'not',
            function ($expression) use ($name, $binds) {
                $expression = empty($expression) ? '' : ", {$expression}" ;
                return $binds
                ? "<?php if (! \Illuminate\Support\Facades\Blade::check('{$name}', {$binds}{$expression})): ?>"
                : "<?php if (! \Illuminate\Support\Facades\Blade::check('{$name}'{$expression})): ?>";
            }
        );

        $this->directive(
            'else'.$name.'not',
            function ($expression) use ($name, $binds) {
                $expression = empty($expression) ? '' : ", {$expression}" ;
                return $binds
                ? "<?php elseif (! \Illuminate\Support\Facades\Blade::check('{$name}', {$binds}{$expression})): ?>"
                : "<?php elseif (! \Illuminate\Support\Facades\Blade::check('{$name}'{$expression})): ?>";
            }
        );
    }

    /**
     * Disable laravel directives what not use in Rebet.
     *
     * @param string $name
     * @param callable|string $thrower function(){ return/throw new XxxxException(); } or a error message for LogicException
     * @return void
     */
    public function disable(string $name, $thrower) : void
    {
        try {
            $thrown = is_string($thrower) ? LogicException::by($thrower) : $thrower() ;
        } catch (\Exception $e) {
            $thrown = $e;
        }
        $this->directive($name, function ($expression) use ($thrown) {
            throw $thrown;
        });
    }
}
