<?php

declare(strict_types=1);

namespace Rebet\View\Engine\Blade\Compiler;

use Illuminate\View\Compilers\BladeCompiler as LaravelBladeCompiler;
use Rebet\Tools\Exception\LogicException;
use Rebet\View\Code\Code;
use Rebet\View\Tag\Processor;

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
     * All custom "processors" handlers.
     *
     * @var Processor[]
     */
    protected $processors = [];

    /**
     * Register an "raw" statement directive.
     *
     * @param  string $name
     * @param  string $code
     * @return void
     */
    public function raw(string $name, string $code): void
    {
        $this->directive($name, fn() => "<?php {$code} ?>");
    }

    /**
     * Execute the given name code.
     *
     * @param  string                   $name
     * @param  array<int|string, mixed> $args (default: [])
     * @return bool
     */
    public function execute($name, array $args = [])
    {
        return isset($this->processors[$name]) ? $this->processors[$name]->execute($args) : null ;
    }

    /**
     * Register an "embed" statement directive.
     * If you give '$errors' as binds then you can get the $errors of assigned value as first argument of callback.
     *
     * @param  string        $name
     * @param  string        $open      code to callbak returns like 'echo(', '$var =', 'if(', '' etc
     * @param  Processor     $processor
     * @param  string        $close     code to callbak returns like ');', ';', '):' etc
     * @param  \Closure|null $lf_trimer Line feed that next of tag closing bracket trim or not. function(?string $expression){ return true or false; } (default: null for trim line feed)
     * @param  string|null   $binds     (default: null)
     * @return void
     */
    public function embed(string $name, string $open, Processor $processor, string $close, \Closure|null $lf_trimer = null, string|null $binds = null): void
    {
        $this->processors[$name] = $processor;
        $lf_trimer ??= fn(string|null $expression) => true;
        $this->directive($name, function ($expression) use ($name, $open, $close, $binds, $lf_trimer) {
            $lf = $lf_trimer($expression) ? "" : "\n" ;
            return $binds
                ? "<?php {$open} \Illuminate\Support\Facades\Blade::execute('{$name}', [{$binds}, {$expression}]) {$close} ?>{$lf}"
                : "<?php {$open} \Illuminate\Support\Facades\Blade::execute('{$name}', [{$expression}]) {$close} ?>{$lf}"
            ;
        });
    }

    /**
     * Register an "if" (and not) statement directive.
     *
     * @param  string      $name
     * @param  Processor   $processor
     * @param  string|null $binds     (default: null)
     * @return void
     */
    public function case($name, Processor $processor, string|null $binds = null): void
    {
        $this->processors[$name] = $processor;

        $this->directive($name, fn($expression) => $binds
                    ? "<?php if (\Illuminate\Support\Facades\Blade::execute('{$name}', [{$binds}, {$expression}])): ?>"
                    : "<?php if (\Illuminate\Support\Facades\Blade::execute('{$name}', [{$expression}])): ?>");

        $this->directive('else' . $name, fn($expression) => $binds
                ? "<?php elseif (\Illuminate\Support\Facades\Blade::execute('{$name}', [{$binds}, {$expression}])): ?>"
                : "<?php elseif (\Illuminate\Support\Facades\Blade::execute('{$name}', [{$expression}])): ?>");

        $this->directive('end' . $name, fn() => '<?php endif; ?>');

        $this->directive(
            $name . 'not',
            fn($expression) => $binds
                ? "<?php if (! \Illuminate\Support\Facades\Blade::execute('{$name}', [{$binds}, {$expression}])): ?>"
                : "<?php if (! \Illuminate\Support\Facades\Blade::execute('{$name}', [{$expression}])): ?>",
        );

        $this->directive(
            'else' . $name . 'not',
            fn($expression) => $binds
                ? "<?php elseif (! \Illuminate\Support\Facades\Blade::execute('{$name}', [{$binds}, {$expression}])): ?>"
                : "<?php elseif (! \Illuminate\Support\Facades\Blade::execute('{$name}', [{$expression}])): ?>",
        );
    }

    /**
     * Disable laravel directives what not use in Rebet.
     *
     * @param  string          $name
     * @param  callable|string $thrower function(){ return/throw new XxxxException(); } or a error message for LogicException
     * @return void
     */
    public function disable(string $name, $thrower = null): void
    {
        $thrower ??= "The '{$name}' directive is not supported in Rebet." ;
        $this->directive($name, function ($expression) use ($thrower): void {
            throw is_string($thrower) ? new LogicException($thrower) : $thrower() ;
        });
    }
}
