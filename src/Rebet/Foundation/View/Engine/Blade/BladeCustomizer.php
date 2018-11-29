<?php
namespace Rebet\Foundation\View\Engine\Blade;

use Illuminate\View\Compilers\BladeCompiler;
use Rebet\Auth\Auth;
use Rebet\Common\Strings;
use Rebet\Foundation\App;
use Rebet\Translation\Trans;

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
        $blade->if('envnot', function ($env) {
            return !in_array(App::getEnv(), (array)$env);
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
        $blade->if('isnot', function (string ...$roles) {
            return Auth::user()->isnot(...$roles);
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
        $blade->if('cannot', function (string $action, $target, ...$extras) {
            return Auth::user()->cannot($action, $target, ...$extras);
        });

        // ------------------------------------------------
        // [errors] Check error is exists
        // ------------------------------------------------
        // Params:
        //   (none)
        // Usage:
        //   @errors ... @else ... @enderrors
        $blade->directive('errors', function () {
            return '<?php if($errors): ?>';
        });
        $blade->directive('enderrors', function () {
            return '<?php endif; ?>';
        });

        // ------------------------------------------------
        // [error] Output error message
        // ------------------------------------------------
        // Params:
        //   $name  : string - attribute name
        //   $outer : string - outer text/html template with :messages placeholder (default: @errors.outer in /i18n/message.php)
        //   $inner : string - inner text/html template with :message placeholder (default: @errors.inner in /i18n/message.php)
        // Usage:
        //   @error
        //   @error('name')
        //   @error('name', '<div class="error">:messages</div>', "* :message<br>")
        $blade->directive('error', function ($expression = null) {
            [$name, $outer, $inner] = array_pad(explode(',', $expression), 3, null);
            $name  = Strings::trim($name);
            $outer = Strings::trim($outer) ?? Trans::grammar('message', "errors.outer") ?? '<ul class="error">:messages</ul>';
            $inner = Strings::trim($inner) ?? Trans::grammar('message', "errors.inner") ?? '<li>:message</li>';
            return <<<EOS
<?php
(function () use (\$errors) {
    \$messages = '';
    if ({$name})) {
        foreach (\$errors[{$name}] ?? [] as \$message) {
            \$messages .= str_replace(':message', \$message, '{$inner}');
        }
    } else {
        foreach (\$errors ?? [] as \$messages) {
            foreach (\$messages as \$message) {
                \$messages .= str_replace(':message', \$message, '{$inner}');
            }
        }
    }
    echo str_replace(':messages', \$messages, '{$outer}');
})();
?>
EOS;
        });
    }
}
