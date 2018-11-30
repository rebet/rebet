<?php
namespace Rebet\Foundation\View\Engine\Blade;

use Rebet\Auth\Auth;
use Rebet\Foundation\App;
use Rebet\Translation\Trans;
use Rebet\View\Engine\Blade\BladeCompiler;

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
     * define costom directives for Rebet.
     */
    public static function customize(BladeCompiler $blade) : void
    {
        // ------------------------------------------------
        // [env/envnot] Check current environment
        // ------------------------------------------------
        // Params:
        //   $env : string|array - allow enviroments
        // Usage:
        //   @env('local') ... @else ... @endenv
        //   @env(['local', 'testing']) ... @else ... @endenv
        $blade->if('env', function ($env) {
            return in_array(App::getEnv(), (array)$env);
        });

        // ------------------------------------------------
        // [is/isnot] Check current users role (Authorization)
        // ------------------------------------------------
        // Params:
        //   $roles : string - role names
        // Usage:
        //   @is('admin') ... @else ... @endis
        //   @is('user', 'guest') ... @else ... @endis
        //   @is('user', 'guest:post-editable') ... @else ... @endis
        $blade->if('is', function (string ...$roles) {
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
        //   @can('update', $post) ... @else ... @endcan
        //   @can('create', Post::class) ... @else ... @endcan
        //   @can('update', 'remark', $post) ... @else ... @endcan
        $blade->if('can', function (string $action, $target, ...$extras) {
            return Auth::user()->can($action, $target, ...$extras);
        });

        // ------------------------------------------------
        // [errors] Check error is exists
        // ------------------------------------------------
        // Params:
        //   $name : string - attribute name (default: null)
        // Usage:
        //   @errors ... @else ... @enderrors
        $blade->code('errors', 'if(', function ($errors, $name = null) {
            return $name ? isset($errors[$name]) : !empty($errors) ;
        }, '):', '$errors');
        $blade->directive('enderrors', function () {
            return '<?php endif; ?>';
        });

        // ------------------------------------------------
        // [error] Output error message of given attributes
        // ------------------------------------------------
        // Params:
        //   $names : string|array - attribute names (default: '*')
        //   $outer : string       - outer text/html template with :messages placeholder (default: @errors.outer in /i18n/message.php)
        //   $inner : string       - inner text/html template with :message placeholder (default: @errors.inner in /i18n/message.php)
        // Usage:
        //   @error
        //   @error('name')
        //   @error(['first_name', 'last_name'])
        //   @error('*')
        //   @error('name', <div class="errors"><ul class="error">:messages</ul></div>)
        //   @error('name', <div class="error">:messages</div>, * :message<br>)
        $blade->code('error', 'echo(', function ($errors, $names = '*', $outer = null, $inner = null) {
            $outer  = $outer ?? Trans::grammar('message', "errors.outer") ?? '<ul class="error">:messages</ul>';
            $inner  = $inner ?? Trans::grammar('message', "errors.inner") ?? '<li>:message</li>';
            $output = '';
            if ($names === '*') {
                foreach ($errors ?? [] as $messages) {
                    foreach ($messages as $message) {
                        $output .= str_replace(':message', $message, $inner);
                    }
                }
            } else {
                $names = (array)$names;
                foreach ($names as $name) {
                    foreach ($errors[$name] ?? [] as $message) {
                        $output .= str_replace(':message', $message, $inner);
                    }
                }
            }
            return empty($output) ? '' : str_replace(':messages', $output, $outer) ;
        }, ');', '$errors');

        // ------------------------------------------------
        // [iferror] Output given value if error
        // ------------------------------------------------
        // Params:
        //   $name    : string - attribute names
        //   $iferror : mixed  - return value if error is exists
        //   $else    : mixed  - return value if error is not exists (default: '')
        // Usage:
        //   @iferror('name', 'color: red;')
        //   @iferror('name', 'color: red;', 'color: gleen;')
        $blade->code('iferror', 'echo(', function ($errors, $name, $iferror, $else = '') {
            return isset($errors[$name]) ? $iferror : $else ;
        }, ');', '$errors');

        // ------------------------------------------------
        // [errorclass] Output css error class if error
        // ------------------------------------------------
        // Params:
        //   $name  : string - attribute names
        //   $class : string - class name (default: @errors.class in /i18n/message.php)
        // Usage:
        //   @errorclass('name')
        //   @errorclass('name', 'class_name')
        $blade->code('errorclass', 'echo(', function ($errors, $name, $class = null) {
            $class = $class ?? Trans::grammar('message', "errors.class") ?? 'error';
            return isset($errors[$name]) ? $class : '' ;
        }, ');', '$errors');
    }
}
