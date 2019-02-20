<?php
namespace Rebet\Foundation\View\Engine\Twig;

use Rebet\Auth\Auth;
use Rebet\Foundation\App;
use Rebet\Stream\Stream;
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
        //   {% env is 'local' %} ... {% else %} ... {% endenv %}
        //   {% env is 'local', 'testing' %} ... {% else %} ... {% endenv %}
        $twig->if('env', 'is', function (string ...$env) {
            return in_array(App::getEnv(), $env);
        });

        // ------------------------------------------------
        // [prefix] Output route prefix
        // ------------------------------------------------
        // Params:
        //   (none)
        // Usage:
        //   {% prefix %}
        $twig->code('prefix', null, 'echo(', function ($prefix) {
            return Stream::of($prefix, true)->escape() ;
        }, ');', ['prefix']);
        
        // ------------------------------------------------
        // [role is/role is not] Check current users role (Authorization)
        // ------------------------------------------------
        // Params:
        //   $roles : string - role names
        // Usage:
        //   {% role is 'admin' %} ... {% else %} ... {% endrole %}
        //   {% role is 'user', 'guest' %} ... {% else %} ... {% endrole %}
        //   {% role is 'user' or 'guest' %} ... {% else %} ... {% endrole %}
        //   {% role is 'user', 'guest:post-editable' %} ... {% else %} ... {% endrole %}
        $twig->if('role', 'is', function (string ...$roles) {
            return Auth::user()->is(...$roles);
        });
    }
}
