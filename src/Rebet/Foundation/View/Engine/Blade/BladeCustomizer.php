<?php
namespace Rebet\Foundation\View\Engine\Blade;

use Rebet\Auth\Auth;
use Rebet\Foundation\App;
use Rebet\Stream\Stream;
use Rebet\Translation\Translator;
use Rebet\View\Engine\Blade\Compiler\BladeCompiler;

/**
 * Blade custom directives for Rebet
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BladeCustomizer
{
    /**
     * @var string of field name
     */
    protected static $field = null;

    /**
     * define costom directives for Rebet.
     */
    public static function customize(BladeCompiler $blade) : void
    {
        // ------------------------------------------------
        // [env/envnot] Check current environment
        // ------------------------------------------------
        // Params:
        //   $env : string - allow enviroments
        // Usage:
        //   @env('local') ... @elseenv('testing') ... @else ... @endenv
        //   @env('local', 'testing') ... @else ... @endenv
        $blade->if('env', function (string ...$env) {
            return in_array(App::getEnv(), $env);
        });

        // ------------------------------------------------
        // [prefix] Output route prefix
        // ------------------------------------------------
        // Params:
        //   (none)
        // Usage:
        //   @prefix
        $blade->code('prefix', 'echo(', function ($prefix) {
            return Stream::of($prefix, true)->escape() ;
        }, ');', '$prefix ?? null');
        
        // ------------------------------------------------
        // [role/rolenot] Check current users role (Authorization)
        // ------------------------------------------------
        // Params:
        //   $roles : string - role names
        // Usage:
        //   @role('admin') ... @elserole('user') ... @else ... @endrole
        //   @role('user', 'guest') ... @else ... @endrole
        //   @role('user', 'guest:post-editable') ... @else ... @endrole
        $blade->if('role', function (string ...$roles) {
            return Auth::user()->is(...$roles);
        });

        // ------------------------------------------------
        // [can/cannot] Check policy for target to current user (Authorization)
        // ------------------------------------------------
        // Params:
        //   $action : string        - action name
        //   $target : string|object - target object or class or any name
        //   $extras : mixed         - extra arguments
        // Usage:
        //   @can('update', $post) ... @elsecan('create', Post::class) ... @else ... @endcan
        //   @can('create', Post::class) ... @else ... @endcan
        //   @can('update', 'remark', $post) ... @else ... @endcan
        $blade->if('can', function (string $action, $target, ...$extras) {
            return Auth::user()->can($action, $target, ...$extras);
        });

        // ------------------------------------------------
        // [field] Bind field attribute name
        // ------------------------------------------------
        // Params:
        //   $name : string|null - attribute name
        // Usage:
        //   @field('email') ... @endfield
        // Under @field:
        //   @field
        // Note:
        //   It does not correspond to nesting.
        $field = &static::$field;
        $blade->code('field', 'echo (', function ($name = null) use (&$field) {
            if ($name === null) {
                return $field;
            }
            $field = $name;
            return '';
        }, ');');
        $blade->code('endfield', '', function () use (&$field) {
            $field = null;
        }, ';');

        // ------------------------------------------------
        // [errors] Check error is exists
        // ------------------------------------------------
        // Params:
        //   $name : string - attribute name (default: null)
        // Usage:
        //   @errors ... @else ... @enderrors
        //   @errors('email') ... @else ... @enderrors
        // Under @field:
        //   @errors ... @else ... @enderrors
        $blade->code('errors', 'if(', function ($errors, $name = null) use (&$field) {
            $errors = Stream::of($errors, true);
            $name   = $name ?? $field ;
            return $name ? !$errors[$name]->isBlank() : !$errors->isEmpty() ;
        }, '):', '$errors ?? null');
        $blade->raw('enderrors', 'endif;');

        // ------------------------------------------------
        // [error] Output error message of given attributes
        // ------------------------------------------------
        // Params:
        //   $names : string|array - attribute names (default: @field if exists, otherwise '*')
        //   $outer : string       - outer text/html template with :messages placeholder (default: @errors.outer in /i18n/message.php)
        //   $inner : string       - inner text/html template with :message placeholder (default: @errors.inner in /i18n/message.php)
        // Usage:
        //   @error
        //   @error('email')
        //   @error(['first_name', 'last_name'])
        //   @error('*')
        //   @error('email', '<div class="errors"><ul class="error">:messages</ul></div>')
        //   @error('email', '<div class="error">:messages</div>', '* :message<br>')
        // Under @field:
        //   @error
        //   @error('<div class="errors"><ul class="error">:messages</ul></div>')
        //   @error('<div class="error">:messages</div>', '* :message<br>')
        $blade->code('error', 'echo(', function ($errors, ...$args) use (&$field) {
            $errors                  = Stream::of($errors, true);
            [$names, $outer, $inner] = array_pad($field ? array_merge([$field], $args) : $args, 3, null);

            $names = $names ?? '*' ;
            $outer = $outer ?? Translator::grammar('message', "errors.outer") ?? '<ul class="error">:messages</ul>'."\n";
            $inner = $inner ?? Translator::grammar('message', "errors.inner") ?? '<li>:message</li>';

            $output = '';
            if ($names === '*') {
                foreach ($errors as $messages) {
                    foreach ($messages as $message) {
                        $output .= str_replace(':message', $message->escape(), $inner);
                    }
                }
            } else {
                $names = (array)$names;
                foreach ($names as $name) {
                    foreach ($errors[$name] as $message) {
                        $output .= str_replace(':message', $message->escape(), $inner);
                    }
                }
            }
            return empty($output) ? '' : str_replace(':messages', $output, $outer) ;
        }, ');', '$errors ?? null');

        // ------------------------------------------------
        // [iferror] Output given value if error
        // ------------------------------------------------
        // Params:
        //   $name    : string - attribute names
        //   $iferror : mixed  - return value if error is exists
        //   $else    : mixed  - return value if error is not exists (default: '')
        // Usage:
        //   @iferror('email', 'color: red;')
        //   @iferror('email', 'color: red;', 'color: gleen;')
        // Under @field:
        //   @iferror('color: red;')
        //   @iferror('color: red;', 'color: gleen;')
        $blade->code('iferror', 'echo(', function ($errors, ...$args) use (&$field) {
            $errors                  = Stream::of($errors, true);
            [$name, $iferror, $else] = array_pad($field ? array_merge([$field], $args) : $args, 3, null);
            return $errors[$name]->isBlank() ? $else : $iferror ?? '' ;
        }, ');', '$errors ?? null');

        // ------------------------------------------------
        // [e] Output error grammers if error
        // ------------------------------------------------
        // Params:
        //   $name    : string - attribute name (default: @field if exists)
        //   $grammer : string - glammer name of @errors in /i18n/message.php.
        // Usage:
        //   @e('email', 'class')
        //   @e('email', 'icon')
        // Under @field:
        //   @e('class')
        //   @e('icon')
        $blade->code('e', 'echo(', function ($errors, ...$args) use (&$field) {
            $errors           = Stream::of($errors, true);
            [$name, $grammer] = array_pad($field ? array_merge([$field], $args) : $args, 2, null);
            [$value, $else]   = array_pad((array)Translator::grammar('message', "errors.{$grammer}"), 2, '');
            return $errors[$name]->isBlank() ? $else : $value ;
        }, ');', '$errors ?? null');

        // ------------------------------------------------
        // [input] Output input data
        // ------------------------------------------------
        // Params:
        //   $name    : string - attribute name
        //   $default : mixed  - default valule (default: '')
        // Usage:
        //   @input('email')
        //   @input('email', $user->email)
        // Under @field:
        //   @input
        //   @input($user->email)
        $blade->code('input', 'echo(', function ($input, ...$args) use (&$field) {
            $input            = Stream::of($input, true);
            [$name, $default] = array_pad($field ? array_merge([$field], $args) : $args, 2, null);
            return $input[$name]->default($default)->escape();
        }, ');', '$input ?? null');
    }
}
