<?php
namespace Rebet\Foundation\View\Engine\Blade;

use Illuminate\View\Compilers\BladeCompiler;
use Rebet\Auth\Auth;
use Rebet\Foundation\App;

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
        // Check current environment
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
        // Check current users role (Authorization)
        // ------------------------------------------------
        // Params:
        //   $roles : string - role names
        // Usage:
        //   @belong('admin') ... @else ... @endbelong
        //   @belong('user', 'guest') ... @else ... @endbelong
        //   @belong('user', 'guest:post-editable') ... @else ... @endbelong
        $blade->if('belong', function (string ...$roles) {
            return Auth::user()->belong(...$roles);
        });

        // ------------------------------------------------
        // Check policy for target to current user (Authorization)
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
    }
}
