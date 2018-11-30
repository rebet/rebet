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
        // [with] Bind attribute name to withable @xxx
        // ------------------------------------------------
        // Params:
        //   $names : string - attribute name
        // Usage:
        //   @with('email') ... @endwith
        // Note:
        //   It does not correspond to nesting.
        $blade->code('with', '$_with = ', function ($name) {
            return $name;
        }, ';');
        $blade->directive('endwith', function () {
            return '<?php $_with = null; ?>';
        });

        // ------------------------------------------------
        // [errors] Check error is exists
        // ------------------------------------------------
        // Params:
        //   $name : string - attribute name (default: null)
        // Usage:
        //   @errors ... @else ... @enderrors
        //   @errors('email') ... @else ... @enderrors
        // Under @with:
        //   @errors ... @else ... @enderrors
        $blade->code('errors', 'if(', function ($errors, $_with, $name = null) {
            $name = $name ?? $_with ;
            return $name ? isset($errors[$name]) : !empty($errors) ;
        }, '):', '$errors, $_with');
        $blade->directive('enderrors', function () {
            return '<?php endif; ?>';
        });

        // ------------------------------------------------
        // [error] Output error message of given attributes
        // ------------------------------------------------
        // Params:
        //   $names : string|array - attribute names (default: @with if exists, otherwise '*')
        //   $outer : string       - outer text/html template with :messages placeholder (default: @errors.outer in /i18n/message.php)
        //   $inner : string       - inner text/html template with :message placeholder (default: @errors.inner in /i18n/message.php)
        // Usage:
        //   @error
        //   @error('email')
        //   @error(['first_name', 'last_name'])
        //   @error('*')
        //   @error('email', '<div class="errors"><ul class="error">:messages</ul></div>')
        //   @error('email', '<div class="error">:messages</div>', '* :message<br>')
        // Under @with:
        //   @error
        //   @error('<div class="errors"><ul class="error">:messages</ul></div>')
        //   @error('<div class="error">:messages</div>', '* :message<br>')
        $blade->code('error', 'echo(', function ($errors, $_with, ...$args) {
            [$names, $outer, $inner] = array_pad($_with ? array_merge([$_with], $args) : $args, 3, null);

            $names = $names ?? '*' ;
            $outer = $outer ?? Trans::grammar('message', "errors.outer") ?? '<ul class="error">:messages</ul>';
            $inner = $inner ?? Trans::grammar('message', "errors.inner") ?? '<li>:message</li>';
        
            $output = '';
            if ($names === '*') {
                foreach ($errors ?? [] as $messages) {
                    foreach ($messages as $message) {
                        $output .= str_replace(':message', htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), $inner);
                    }
                }
            } else {
                $names = (array)$names;
                foreach ($names as $name) {
                    foreach ($errors[$name] ?? [] as $message) {
                        $output .= str_replace(':message', htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), $inner);
                    }
                }
            }
            return empty($output) ? '' : str_replace(':messages', $output, $outer) ;
        }, ');', '$errors, $_with');

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
        // Under @with:
        //   @iferror('color: red;')
        //   @iferror('color: red;', 'color: gleen;')
        $blade->code('iferror', 'echo(', function ($errors, $_with, ...$args) {
            [$name, $iferror, $else] = array_pad($_with ? array_merge([$_with], $args) : $args, 3, null);
            return isset($errors[$name]) ? $iferror : $else ?? '' ;
        }, ');', '$errors, $_with');

        // ------------------------------------------------
        // [e] Output error grammers if error
        // ------------------------------------------------
        // Params:
        //   $name    : string - attribute name (default: @with if exists)
        //   $grammer : string - glammer name of @errors in /i18n/message.php.
        // Usage:
        //   @e('email', 'class')
        //   @e('email', 'icon')
        // Under @with:
        //   @e('class')
        //   @e('icon')
        $blade->code('e', 'echo(', function ($errors, $_with, ...$args) {
            [$name, $grammer] = array_pad($_with ? array_merge([$_with], $args) : $args, 2, null);
            [$value, $else]   = array_pad((array)Trans::grammar('message', "errors.{$grammer}"), 2, '');
            return isset($errors[$name]) ? $value : $else ;
        }, ');', '$errors, $_with');

        // ------------------------------------------------
        // [input] Output input data
        // ------------------------------------------------
        // Params:
        //   $name    : string - attribute name
        //   $default : mixed  - default valule (default: '')
        // Usage:
        //   @input('email')
        //   @input('email', $user->email)
        // Under @with:
        //   @input
        //   @input($user->email)
        $blade->code('input', 'echo(', function ($input, $_with, ...$args) {
            [$name, $default] = array_pad($_with ? array_merge([$_with], $args) : $args, 2, null);
            return htmlspecialchars($input->get($name, $default ?? ''), ENT_QUOTES, 'UTF-8');
        }, ');', '$input, $_with');
    }
}
