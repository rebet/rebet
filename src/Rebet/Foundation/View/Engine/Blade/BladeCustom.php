<?php
namespace Rebet\Foundation\View\Engine\Blade;

use Rebet\Foundation\App;

/**
 * Blade custom directives for Rebet
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BladeCustom
{
    /**
     * If directives
     *
     * @return iterable
     */
    public static function if() : iterable
    {
        yield ['env', function ($env) {
            return in_array(App::getEnv(), (array)$env) ;
        }];
    }

    /**
     * Directives
     *
     * @return iterable
     */
    public static function directive() : iterable
    {
        return [];
    }

    /**
     * Component directives
     *
     * @return iterable
     */
    public static function component() : iterable
    {
        return [];
    }

    /**
     * Include directives
     *
     * @return iterable
     */
    public static function include() : iterable
    {
        return [];
    }
}
