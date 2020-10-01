<?php
namespace Rebet\Application\View\Engine\Blade;

use Rebet\Tools\Utility\Path;
use Rebet\Database\Pagination\Paginator;
use Rebet\Application\View\Engine\BuiltinTagProcessors;
use Rebet\Http\Request;
use Rebet\Tools\Translation\FileDictionary;
use Rebet\Tools\Translation\Translator;
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
     * Define costom directives for Rebet.
     *
     * @param Blade $blade
     */
    public static function customize(Blade $blade) : void
    {
        $blade->appendPath(__DIR__.'/views');
        $compiler = $blade->compiler();

        // Line feed handler that next of tag closing bracket.
        $lf_trim_if_args = function (?string $expression) { return !empty($expression); };
        $lf_not_trim     = function (?string $expression) { return false; };

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
        $compiler->case('env', BuiltinTagProcessors::env());

        // ------------------------------------------------
        // [prefix] Output route prefix
        // ------------------------------------------------
        // Params:
        //   (none)
        // Usage:
        //   @prefix
        $compiler->embed('prefix', 'echo(', BuiltinTagProcessors::prefix(), ');', $lf_not_trim, '$prefix ?? null');

        // ------------------------------------------------
        // [role/rolenot] Check current users role (Authorization)
        // ------------------------------------------------
        // Params:
        //   $roles : string - role names
        // Usage:
        //   @role('admin') ... @elserole('user') ... @else ... @endrole
        //   @role('user', 'guest') ... @else ... @endrole
        //   @role('user', 'guest:post-editable') ... @else ... @endrole
        $compiler->case('role', BuiltinTagProcessors::role());

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
        $compiler->case('can', BuiltinTagProcessors::can());

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
        $compiler->embed('field', 'echo (', BuiltinTagProcessors::field(), ');', $lf_trim_if_args);
        $compiler->embed('endfield', '', BuiltinTagProcessors::endfield(), ';');

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
        //   @error('email', 'inner' => '* :message<br>')
        // Usage: <In @field block>
        //   @error
        //   @error('<div class="errors"><ul class="error">:messages</ul></div>')
        //   @error('<div class="error">:messages</div>', '* :message<br>')
        //   @error('inner' => '* :message<br>')
        $compiler->embed('error', 'echo(', BuiltinTagProcessors::error(), ');', $lf_not_trim, '$errors ?? null');

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
        $compiler->case('errors', BuiltinTagProcessors::errors(), '$errors ?? null');

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
        $compiler->embed('iferror', 'echo(', BuiltinTagProcessors::iferror(), ');', $lf_not_trim, '$errors ?? null');

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
        $compiler->embed('e', 'echo(', BuiltinTagProcessors::e(), ');', $lf_not_trim, '$errors ?? null');

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
        $compiler->embed('input', 'echo(', BuiltinTagProcessors::input(), ');', $lf_not_trim, '$input ?? null');

        // ------------------------------------------------
        // [csrf_token] Output csrf token value
        // ------------------------------------------------
        // Params:
        //   $scope : mixed - if the scope is given then token become one time token.
        // Usage:
        //   @csrf_token
        //   @csrf_token('user', 'edit')
        //   @csrf_token('article', 'edit', $article->article_id)
        $compiler->embed('csrf_token', 'echo(', BuiltinTagProcessors::csrfToken(), ');', $lf_not_trim);

        // ------------------------------------------------
        // [csrf] Output csrf token hidden field tag
        // ------------------------------------------------
        // Params:
        //   $scope : mixed - if the scope is given then token become one time token.
        // Usage:
        //   @csrf
        //   @csrf('user', 'edit')
        //   @csrf('article', 'edit', $article->article_id)
        $compiler->embed('csrf', 'echo(', BuiltinTagProcessors::csrf(), ');', $lf_not_trim);

        // ------------------------------------------------
        // [lang] Translate given message to current locale
        // ------------------------------------------------
        // Params:
        //   $key         : string - translate message key.
        //   $replacement : array  - parameter of translate message. (default: [])
        //   $selector    : mixed  - translation message selector for example pluralize. (default: null)
        //   $locale      : string - locale that translate to. (default: null for current locale)
        // Usage:
        //   @lang('messages.welcome')
        //   @lang('messages.welcome', ['name' => 'Jhon'])
        //   @lang('messages.tags', ['tags' => $tags], count($tags))
        //   @lang('messages.tags', ['tags' => $tags], count($tags), 'en')
        $compiler->embed('lang', 'echo(', BuiltinTagProcessors::lang(), ');', $lf_not_trim);

        // ------------------------------------------------
        // [paginate] Pagination link output tag
        // ------------------------------------------------
        // https://github.com/laravel/framework/blob/6.x/src/Illuminate/Pagination/resources/views/bootstrap-4.blade.php
        // Params:
        //   $paginator : Paginator     - the paginator object.
        //   $options   : array         - pagination options.
        //     - template : string|null - the template name of pagination. (default: null for use default template)
        //     - action   : string|null - the paginate link action url. (default: null for use Request::getRequestPath())
        //     - query    : array|null  - the paginate link query parameters. (default: null for use Request::input())
        //     - anchor   : string|null - the paginate link action url anchor. (default: null)
        //     - append   : array|null  - the appended paginate link query parameters that append/override 'query' parameters. (default: null)
        //     - reject   : array|null  - the rejected link query parameter keys from 'query' parameters. (default: null)
        //     - *        : mixed       - Other options will pass through to paginate template, as it is.
        // Usage:
        //   @paginate($users)
        //   @paginate($users, ['template' => 'paginate@semantic-ui'])
        // Note:
        //   Default paginate template can be changed by Rebet\Application\App.paginate.default_template configure.
        $compiler->embed('paginate', 'echo(', BuiltinTagProcessors::paginate(), ');', $lf_not_trim);
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


// ---------------------------------------------------------
// Add library default translation resource
// ---------------------------------------------------------
Translator::addResourceTo(FileDictionary::class, Path::normalize(__DIR__.'/i18n'), 'pagination');
