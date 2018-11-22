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
        //   @env(['local','testing']) ... @else ... @endenv
        $blade->if('env', function ($env) {
            return in_array(App::getEnv(), (array)$env);
        });

        // ------------------------------------------------
        // Check Policy and Gate (Authorization)
        // ------------------------------------------------
        // Params:
        //   $action : string - action
        //   $target : mixed  - action
        // Usage:
        //   @can('admin') ... @else ... @endcan
        //   @can('update', $post) ... @else ... @endcan
        //   @can('create', Post::class) ... @else ... @endcan
        $blade->if('can', function ($action, ...$target) {
            return Auth::user()->can($action, ...$target);
        });
    }
}
