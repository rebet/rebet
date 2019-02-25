<?php
namespace Rebet\Foundation\View\Engine\Twig;

use Rebet\Auth\Auth;
use Rebet\Foundation\App;
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
        $twig->if('env', 'is', [',', 'or'], function (string ...$env) {
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
        $twig->if('role', 'is', [',', 'or'], function (string ...$roles) {
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
        $twig->if('can', null, ['with', ',', 'and'], function (string $action, $target, ...$extras) {
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
        // [errors] Check error is exists
        // ------------------------------------------------
        // Params:
        //   $name : string - attribute name (default: null)
        // Usage:
        //   {% errors %} ... {% else %} ... {% enderrors %}
        //   {% errors 'email' %} ... {% else %} ... {% enderrors %}
        // Under {% field %}:
        //   {% errors %} ... {% else %} ... {% enderrors %}
        $twig->code('errors', '', [], 'if(', function ($errors, $name = null) use (&$field) {
            $errors = Stream::of($errors, true);
            $name   = $name ?? $field ;
            return $name ? !$errors[$name]->isBlank() : !$errors->isEmpty() ;
        }, '){', ['errors']);
        $twig->raw('enderrors', '}');

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
        $twig->code('error', '', ['format', 'by', ','], 'echo(', function ($errors, ...$args) use (&$field) {
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
    }
}
