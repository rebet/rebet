<?php
namespace Rebet\Foundation\View\Engine\Blade;

use Rebet\Foundation\App;
use Illuminate\View\Compilers\BladeCompiler;

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
    public static function customize(BladeCompiler &$blade) : void
    {
        // ------------------------------------------------
        // env check
        // ------------------------------------------------
        $blade->if('env', function ($env) {
            return in_array(App::getEnv(), (array)$env);
        });
    }
}
