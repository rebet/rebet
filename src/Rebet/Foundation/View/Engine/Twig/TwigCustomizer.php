<?php
namespace Rebet\Foundation\View\Engine\Twig;

use Rebet\Auth\Auth;
use Rebet\Foundation\App;
use Rebet\Http\Session\Session;
use Rebet\Stream\Stream;
use Rebet\Translation\Translator;
use Rebet\View\Engine\Twig\Environment\Environment;

/**
 * Twig custom extentions for Rebet
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class TwigCustomizer
{
    /**
     * @var string of field name
     */
    protected static $field = null;

    /**
     * define costom extentions for Rebet.
     */
    public static function customize(Environment $twig) : void
    {
        // ------------------------------------------------
        // [env is/env is not] Check current environment
        // ------------------------------------------------
        // Params:
        //   $env : string|array - allow enviroments
        // Usage:
        //   {% env is 'local' %} ... {% elseenv is 'testing' %} ... {% else %} ... {% endenv %}
        //   {% env is 'local', 'testing' %} ... {% else %} ... {% endenv %}
        $twig->if('env', 'is', ['/,/*', '/or/'], function (string ...$env) {
            return in_array(App::getEnv(), $env);
        });

        // ------------------------------------------------
        // [prefix] Output route prefix
        // ------------------------------------------------
        // Params:
        //   (none)
        // Usage:
        //   {% prefix %}
        $twig->code('prefix', null, [], 'echo(', function ($prefix) {
            return Stream::of($prefix, true)->escape() ;
        }, ');', ['prefix']);

        // ------------------------------------------------
        // [role is/role is not] Check current users role (Authorization)
        // ------------------------------------------------
        // Params:
        //   $roles : string - role names
        // Usage:
        //   {% role is 'admin' %} ... {% elserole is 'user' %} ... {% else %} ... {% endrole %}
        //   {% role is 'user', 'guest' %} ... {% else %} ... {% endrole %}
        //   {% role is 'user' or 'guest' %} ... {% else %} ... {% endrole %}
        //   {% role is 'user', 'guest:post-editable' %} ... {% else %} ... {% endrole %}
        $twig->if('role', 'is', ['/,/*', '/or/'], function (string ...$roles) {
            return Auth::user()->is(...$roles);
        });

        // ------------------------------------------------
        // [can/can not] Check policy for target to current user (Authorization)
        // ------------------------------------------------
        // Params:
        //   $action : string        - action name
        //   $target : string|object - target object or class or any name
        //   $extras : mixed         - extra arguments
        // Usage:
        //   {% can 'update' $post %} ... {% elsecan 'create' Post::class %} ... {% else %} ... {% endcan %}
        //   {% can 'create' Post::class %} ... {% else %} ... {% endcan %}
        //   {% can 'update' 'remark' with $post %} ... {% else %} ... {% endcan %}
        //   {% can 'update' $post with $a, $b and $c %} ... {% else %} ... {% endcan %}
        $twig->if('can', null, ['/with/', '/,/*', '/and/'], function (string $action, $target, ...$extras) {
            return Auth::user()->can($action, $target, ...$extras);
        });

        // ------------------------------------------------
        // [field] Bind field attribute name
        // ------------------------------------------------
        // Params:
        //   $name : string - attribute name
        // Usage:
        //   {% field 'email' %} ... {% endfield %}
        // Under {% field %}:
        //   {% field %}
        // Note:
        //   It does not correspond to nesting.
        $field = &static::$field;
        $twig->code('field', '', [], 'echo(', function ($name = null) use (&$field) {
            if ($name === null) {
                return Stream::of($field, true)->escape();
            }
            $field = $name;
            return '';
        }, ');');
        $twig->code('endfield', '', [], '', function () use (&$field) {
            $field = null;
        }, ';');

        // ------------------------------------------------
        // [error] Output error message of given attributes
        // ------------------------------------------------
        // Params:
        //   $names : string|array - attribute names (default: {% field %} if exists, otherwise '*')
        //   $outer : string       - outer text/html template with :messages placeholder (default: @errors.outer in /i18n/message.php)
        //   $inner : string       - inner text/html template with :message placeholder (default: @errors.inner in /i18n/message.php)
        // Usage:
        //   {% error %}
        //   {% error 'email' %}
        //   {% error ['first_name', 'last_name'] %}
        //   {% error '*' %}
        //   {% error 'email' format by '<div class="errors"><ul class="error">:messages</ul></div>' %}
        //   {% error 'email' format by '<div class="error">:messages</div>', '* :message<br>' %}
        // Under {% field %}:
        //   {% error %}
        //   {% error format by '<div class="errors"><ul class="error">:messages</ul></div>' %}
        //   {% error format by '<div class="error">:messages</div>', '* :message<br>' %}
        $twig->code('error', '', ['/format/', '/by/', '/,/'], 'echo(', function ($errors, ...$args) use (&$field) {
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
        }, ');', ['errors']);

        // ------------------------------------------------
        // [errors/errors not] Check error is exists
        // ------------------------------------------------
        // Params:
        //   $name : string - attribute name (default: null)
        // Usage:
        //   {% errors %} ... {% else %} ... {% enderrors %}
        //   {% errors 'email' %} ... {% else %} ... {% enderrors %}
        // Under {% field %}:
        //   {% errors %} ... {% else %} ... {% enderrors %}
        $twig->if('errors', '', [], function ($errors, $name = null) use (&$field) {
            $errors = Stream::of($errors, true);
            $name   = $name ?? $field ;
            return $name ? !$errors[$name]->isBlank() : !$errors->isEmpty() ;
        }, ['errors']);

        // ------------------------------------------------
        // [iferror] Output given value if error
        // ------------------------------------------------
        // Params:
        //   $name : string - attribute names
        //   $then : mixed  - return value if error is exists
        //   $else : mixed  - return value if error is not exists (default: '')
        // Usage:
        //   {% iferror 'email' then 'color: red;' %}
        //   {% iferror 'email' then 'color: red;' else 'color: gleen;' %}
        //   {% iferror 'email' ? 'color: red;' : 'color: gleen;' %}
        // Under {% field %}:
        //   {% iferror then 'color: red;' %}
        //   {% iferror then 'color: red;' else 'color: gleen;' %}
        //   {% iferror ? 'color: red;' : 'color: gleen;' %}
        $twig->code('iferror', '', ['/then|\?/', '/else|:/'], 'echo(', function ($errors, ...$args) use (&$field) {
            $errors               = Stream::of($errors, true);
            [$name, $then, $else] = array_pad($field ? array_merge([$field], $args) : $args, 3, null);
            return $errors[$name]->isBlank() ? $else : $then ?? '' ;
        }, ');', ['errors']);

        // ------------------------------------------------
        // [e] Output error grammers if error
        // ------------------------------------------------
        // Params:
        //   $name    : string - attribute name (default: @field if exists)
        //   $grammer : string - glammer name of @errors in /i18n/message.php.
        // Usage:
        //   {% e 'email' 'class' %}
        //   {% e 'email' 'icon' %}
        // Under {% field %}:
        //   {% e 'class' %}
        //   {% e 'icon' %}
        $twig->code('e', '', [], 'echo(', function ($errors, ...$args) use (&$field) {
            $errors           = Stream::of($errors, true);
            [$name, $grammer] = array_pad($field ? array_merge([$field], $args) : $args, 2, null);
            [$value, $else]   = array_pad((array)Translator::grammar('message', "errors.{$grammer}"), 2, '');
            return $errors[$name]->isBlank() ? $else : $value ;
        }, ');', ['errors']);

        // ------------------------------------------------
        // [input] Output input data
        // ------------------------------------------------
        // Params:
        //   $name    : string - attribute name
        //   $default : mixed  - default valule (default: '')
        // Usage:
        //   {% input 'email' %}
        //   {% input 'email' default $user->email %}
        //   {% input 'email' ?? $user->email %}
        // Under {% field %}:
        //   {% input %}
        //   {% input default $user->email %}
        //   {% input ?? $user->email %}
        $twig->code('input', '', ['/default|\?\?/'], 'echo(', function ($input, ...$args) use (&$field) {
            $input            = Stream::of($input, true);
            [$name, $default] = array_pad($field ? array_merge([$field], $args) : $args, 2, null);
            return $input[$name]->default($default)->escape();
        }, ');', ['input']);

        // ------------------------------------------------
        // [csrf_token] Output csrf token value
        // ------------------------------------------------
        // Params:
        //   $scope : mixed - if the scope is given then token become one time token.
        // Usage:
        //   {% csrf_token %}
        //   {% csrf_token for 'user', 'edit' %}
        //   {% csrf_token for 'article', 'edit', article.article_id %}
        $twig->code('csrf_token', '', ['/for/', '/,/*'], 'echo(', function (...$scope) {
            $session = Session::current();
            return htmlspecialchars($session->token(...$scope) ?? $session->generateToken(...$scope)) ;
        }, ');');

        // ------------------------------------------------
        // [csrf_field] Output csrf token hidden field tag
        // ------------------------------------------------
        // Params:
        //   $scope : mixed - if the scope is given then token become one time token.
        // Usage:
        //   {% csrf_field %}
        //   {% csrf_field for 'user', 'edit' %}
        //   {% csrf_field for 'article', 'edit', article.article_id %}
        $twig->code('csrf_field', '', ['/for/', '/,/*'], 'echo(', function (...$scope) {
            $session = Session::current();
            $key     = Session::createTokenKey(...$scope);
            $token   = $session->token(...$scope) ?? $session->generateToken(...$scope) ;
            return '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($token).'" />';
        }, ');');
    }
}
