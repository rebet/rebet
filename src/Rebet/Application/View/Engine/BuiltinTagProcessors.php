<?php
namespace Rebet\Application\View\Engine;

use Rebet\Auth\Auth;
use Rebet\Common\Arrays;
use Rebet\Database\Pagination\Paginator;
use Rebet\Application\App;
use Rebet\Http\Request;
use Rebet\Http\Session\Session;
use Rebet\Stream\Stream;
use Rebet\Translation\Translator;
use Rebet\View\Code\Code;
use Rebet\View\Tag\CallbackProcessor;
use Rebet\View\Tag\Processor;
use Rebet\View\Tag\SelectiveProcessor;
use Rebet\View\View;

/**
 * Builtin Tag Processors for Rebet
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BuiltinTagProcessors
{
    /**
     * @var string of field name
     */
    protected static $field = null;

    /**
     * Get current focused field name.
     *
     * @return string|null
     */
    public static function currentField() : ?string
    {
        return static::$field;
    }

    /**
     * Check current environment
     *
     * @return Code
     */
    public static function env() : Processor
    {
        $processor = function (string ...$env) {
            return in_array(App::env(), $env);
        };

        return new CallbackProcessor($processor);
    }

    /**
     * Output route prefix
     *
     * @return Code
     */
    public static function prefix() : Processor
    {
        $processor = function ($prefix) {
            return Stream::of($prefix, true)->escape() ;
        };

        return new CallbackProcessor($processor);
    }

    /**
     * Check current users role (Authorization)
     *
     * @return Code
     */
    public static function role() : Processor
    {
        $processor = function (string ...$roles) {
            return Auth::user()->is(...$roles);
        };

        return new CallbackProcessor($processor);
    }

    /**
     * Check policy for target to current user (Authorization)
     *
     * @return Code
     */
    public static function can() : Processor
    {
        $processor = function (string $action, $target, ...$extras) {
            return Auth::user()->can($action, $target, ...$extras);
        };

        return new CallbackProcessor($processor);
    }

    /**
     * Bind field attribute name
     *
     * @return Code
     */
    public static function field() : Processor
    {
        $processor = function ($name = null) {
            if ($name === null) {
                return BuiltinTagProcessors::$field;
            }
            BuiltinTagProcessors::$field = $name;
            return '';
        };

        return new CallbackProcessor($processor);
    }

    /**
     * Unbind field attribute name
     *
     * @return Code
     */
    public static function endfield() : Processor
    {
        $processor = function () {
            BuiltinTagProcessors::$field = null;
        };

        return new CallbackProcessor($processor);
    }

    /**
     * Output error message of given attributes
     *
     * @return Code
     */
    public static function error() : Processor
    {
        $processor = function ($errors, ?string $names = null, ?string $outer = null, ?string $inner = null) {
            $errors = Stream::of($errors, true);
            $names  = $names ?? '*' ;
            $outer  = $outer ?? Translator::grammar('message', "errors.outer") ?? '<ul class="error">:messages</ul>';
            $inner  = $inner ?? Translator::grammar('message', "errors.inner") ?? '<li>:message</li>';

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
        };

        $selector = function (array $args) use ($processor) {
            if (BuiltinTagProcessors::$field) {
                return function ($errors, ?string $outer = null, ?string $inner = null) use ($processor) { return $processor($errors, BuiltinTagProcessors::$field, $outer, $inner); };
            }
            return $processor;
        };

        return new SelectiveProcessor($selector);
    }

    /**
     * Undocumented function
     *
     * @return Code
     */
    public static function errors() : Processor
    {
        $processor = function ($errors, $name = null) {
            $errors = Stream::of($errors, true);
            $name   = $name ?? BuiltinTagProcessors::$field ;
            return $name ? !$errors[$name]->isBlank() : !$errors->isEmpty() ;
        };

        return new CallbackProcessor($processor);
    }

    /**
     * Output given value if error
     *
     * @return Code
     */
    public static function iferror() : Processor
    {
        $processor = function ($errors, string $name, $then, $else = null) {
            $errors = Stream::of($errors, true);
            return !$errors[$name]->isBlank() ? $then : $else ?? '' ;
        };

        $selector = function (array $args) use ($processor) {
            if (BuiltinTagProcessors::$field) {
                return function ($errors, $then, $else = null) use ($processor) {
                    return $processor($errors, BuiltinTagProcessors::$field, $then, $else);
                };
            }

            return $processor;
        };

        return new SelectiveProcessor($selector);
    }

    /**
     * Output error grammers if error
     *
     * @return Code
     */
    public static function e() : Processor
    {
        $processor = function ($errors, string $name, string $grammer) {
            $errors         = Stream::of($errors, true);
            [$value, $else] = array_pad((array)Translator::grammar('message', "errors.{$grammer}"), 2, '');
            return $errors[$name]->isBlank() ? $else : $value ;
        };

        $selector = function (array $args) use ($processor) {
            if (BuiltinTagProcessors::$field) {
                return function ($errors, string $grammer) use ($processor) {
                    return $processor($errors, BuiltinTagProcessors::$field, $grammer);
                };
            }

            return $processor;
        };

        return new SelectiveProcessor($selector);
    }

    /**
     * Output input data
     *
     * @return Code
     */
    public static function input() : Processor
    {
        $processor = function ($input, string $name, $default = null) {
            $input = Stream::of($input, true);
            return $input[$name]->default($default)->escape();
        };

        $selector = function (array $args) use ($processor) {
            if (BuiltinTagProcessors::$field) {
                return function ($errors, $default = null) use ($processor) {
                    return $processor($errors, BuiltinTagProcessors::$field, $default);
                };
            }

            return $processor;
        };

        return new SelectiveProcessor($selector);
    }

    /**
     * Output csrf token value
     *
     * @return Code
     */
    public static function csrfToken() : Processor
    {
        $processor = function (...$scope) {
            $session = Session::current();
            return htmlspecialchars($session->token(...$scope) ?? $session->generateToken(...$scope)) ;
        };

        return new CallbackProcessor($processor);
    }

    /**
     * Output csrf token hidden field tag
     *
     * @return Code
     */
    public static function csrf() : Processor
    {
        $processor = function (...$scope) {
            $session = Session::current();
            $key     = Session::createTokenKey(...$scope);
            $token   = $session->token(...$scope) ?? $session->generateToken(...$scope) ;
            return '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($token).'" />';
        };

        return new CallbackProcessor($processor);
    }

    /**
     * Translate given message to current locale
     *
     * @return Code
     */
    public static function lang() : Processor
    {
        $processor = function (string $key, array $replacement = [], $selector = null, ?string $locale = null) {
            $replacement = array_map(function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }, $replacement);
            return Translator::get($key, $replacement, $selector, true, $locale);
        };

        return new CallbackProcessor($processor);
    }

    /**
     * Pagination link output tag
     * Note: Default paginate template can be changed by Rebet\Application\App.paginate.default_template configure.
     *
     * @return Code
     */
    public static function paginate() : Processor
    {
        $processor = function (Paginator $paginator, array $options = []) {
            $request  = Request::current();
            $template = Arrays::remove($options, 'template') ?? App::config('paginate.default_template');
            $action   = Arrays::remove($options, 'action') ?? $request->getRequestPath();
            $query    = array_merge(Arrays::remove($options, 'query') ?? $request->input(), Arrays::remove($options, 'append') ?? []);
            $anchor   = Arrays::remove($options, 'anchor');
            $anchor   = $anchor ? "#{$anchor}" : '' ;
            Arrays::forget($query, Arrays::remove($options, 'reject') ?? []);
            $page_name = App::config('paginate.page_name');
            unset($query[$page_name]);
            return View::of($template)->with(array_merge($options, [
                'paginator' => $paginator->action($action, $page_name, $anchor)->with($query)
            ]))->render();
        };

        return new CallbackProcessor($processor);
    }
}
