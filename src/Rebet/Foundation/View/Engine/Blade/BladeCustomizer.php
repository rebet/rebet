<?php
namespace Rebet\Foundation\View\Engine\Blade;

use Rebet\Auth\Auth;
use Rebet\Database\Pagination\Pager;
use Rebet\Foundation\App;
use Rebet\Http\Session\Session;
use Rebet\Stream\Stream;
use Rebet\Translation\Translator;
use Rebet\View\Engine\Blade\Blade;
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
     * Define costom directives for Rebet.
     *
     * @param Blade $blade
     */
    public static function customize(Blade $blade) : void
    {
        $blade->appendPath(__DIR__.'/views');
        $compiler = $blade->compiler();

        // ------------------------------------------------
        // Disable laravel blade built-in directives that not use in Rebet
        // ------------------------------------------------
        static::disable($compiler);

        // ------------------------------------------------
        // [env/envnot] Check current environment
        // ------------------------------------------------
        // Params:
        //   $env : string - allow enviroments
        // Usage:
        //   @env('local') ... @elseenv('testing') ... @else ... @endenv
        //   @env('local', 'testing') ... @else ... @endenv
        $compiler->if('env', function (string ...$env) {
            return in_array(App::getEnv(), $env);
        });

        // ------------------------------------------------
        // [prefix] Output route prefix
        // ------------------------------------------------
        // Params:
        //   (none)
        // Usage:
        //   @prefix
        $compiler->code('prefix', 'echo(', function ($prefix) {
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
        $compiler->if('role', function (string ...$roles) {
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
        $compiler->if('can', function (string $action, $target, ...$extras) {
            return Auth::user()->can($action, $target, ...$extras);
        });

        // ------------------------------------------------
        // [field] Bind field attribute name
        // ------------------------------------------------
        // Params:
        //   $name : string|null - attribute name
        // Usage: <bind field name>
        //   @field('email') ... @endfield
        // Usage: <In @field block>
        //   @field
        // Note:
        //   It does not correspond to nesting.
        $field = &static::$field;
        $compiler->code('field', 'echo (', function ($name = null) use (&$field) {
            if ($name === null) {
                return $field;
            }
            $field = $name;
            return '';
        }, ');');
        $compiler->code('endfield', '', function () use (&$field) {
            $field = null;
        }, ';');

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
        // Usage: <In @field block>
        //   @error
        //   @error('<div class="errors"><ul class="error">:messages</ul></div>')
        //   @error('<div class="error">:messages</div>', '* :message<br>')
        $compiler->code('error', 'echo(', function ($errors, ...$args) use (&$field) {
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
        // [errors/errorsnot] Check error is exists
        // ------------------------------------------------
        // Params:
        //   $name : string - attribute name (default: null)
        // Usage:
        //   @errors ... @else ... @enderrors
        //   @errors('email') ... @else ... @enderrors
        // Usage: <In @field block>
        //   @errors ... @else ... @enderrors
        $compiler->if('errors', function ($errors, $name = null) use (&$field) {
            $errors = Stream::of($errors, true);
            $name   = $name ?? $field ;
            return $name ? !$errors[$name]->isBlank() : !$errors->isEmpty() ;
        }, '$errors ?? null');

        // ------------------------------------------------
        // [iferror] Output given value if error
        // ------------------------------------------------
        // Params:
        //   $name : string - attribute names
        //   $then : mixed  - return value if error is exists
        //   $else : mixed  - return value if error is not exists (default: '')
        // Usage:
        //   @iferror('email', 'color: red;')
        //   @iferror('email', 'color: red;', 'color: gleen;')
        // Usage: <In @field block>
        //   @iferror('color: red;')
        //   @iferror('color: red;', 'color: gleen;')
        $compiler->code('iferror', 'echo(', function ($errors, ...$args) use (&$field) {
            $errors               = Stream::of($errors, true);
            [$name, $then, $else] = array_pad($field ? array_merge([$field], $args) : $args, 3, null);
            return $errors[$name]->isBlank() ? $else : $then ?? '' ;
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
        // Usage: <In @field block>
        //   @e('class')
        //   @e('icon')
        $compiler->code('e', 'echo(', function ($errors, ...$args) use (&$field) {
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
        // Usage: <In @field block>
        //   @input
        //   @input($user->email)
        $compiler->code('input', 'echo(', function ($input, ...$args) use (&$field) {
            $input            = Stream::of($input, true);
            [$name, $default] = array_pad($field ? array_merge([$field], $args) : $args, 2, null);
            return $input[$name]->default($default)->escape();
        }, ');', '$input ?? null');

        // ------------------------------------------------
        // [csrf_token] Output csrf token value
        // ------------------------------------------------
        // Params:
        //   $scope : mixed - if the scope is given then token become one time token.
        // Usage:
        //   @csrf_token
        //   @csrf_token('user', 'edit')
        //   @csrf_token('article', 'edit', $article->article_id)
        $compiler->code('csrf_token', 'echo(', function (...$scope) {
            $session = Session::current();
            return htmlspecialchars($session->token(...$scope) ?? $session->generateToken(...$scope)) ;
        }, ');');

        // ------------------------------------------------
        // [csrf] Output csrf token hidden field tag
        // ------------------------------------------------
        // Params:
        //   $scope : mixed - if the scope is given then token become one time token.
        // Usage:
        //   @csrf
        //   @csrf('user', 'edit')
        //   @csrf('article', 'edit', $article->article_id)
        $compiler->code('csrf', 'echo(', function (...$scope) {
            $session = Session::current();
            $key     = Session::createTokenKey(...$scope);
            $token   = $session->token(...$scope) ?? $session->generateToken(...$scope) ;
            return '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($token).'" />';
        }, ');');

        // ------------------------------------------------
        // [paginate] Pagination link output tag
        // ------------------------------------------------
        // Params:
        //   $paginator : Paginator   - the paginator object.
        //   $template  : string|null - the template name of pagination. (default: null for use default template)
        // Usage:
        //   @paginate($users)
        //   @paginate($users, 'semantic-ui')
        // Note:
        //   Default paginate template can be changed by Rebet\Database\Pagination\Pager.default_template configure.
        $compiler->code('paginate', 'echo(', function ($paginator, $template = null) use ($blade) {
            return $blade->render($template ?? Pager::config('default_template'), ['paginator' => $paginator]);
        }, ');');
    }

    /**
     * Disable laravel directives what not use in Rebet.
     *
     * @param BladeCompiler $compiler
     * @return void
     */
    protected static function disable(BladeCompiler $compiler) : void
    {
        $compiler->disable('auth', "Unsupported directive '@auth' found. In Rebet, you should use '@role' directive instead.");
        $compiler->disable('guest', "Unsupported directive '@guest' found. In Rebet, you should use '@role' directive instead.");
        // @inject
    }
}
