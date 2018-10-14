<?php
namespace Rebet\Foundation\View\Engine\Smarty;

use Rebet\Foundation\App;
use Rebet\View\Engine\Smarty\Smarty;

/**
 * Smarty custom plugins for Rebet
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SmartyCustomizer
{
    /**
     * define costom plugins for Rebet.
     */
    public static function customize(Smarty $smarty) : void
    {
        // ------------------------------------------------
        // Check current environment
        // ------------------------------------------------
        // Params:
        //   in     : string|array - allow enviroments
        //   not_in : string|array - reject enviroments
        // Usage:
        //   {env in="local"} ... {/env}
        //   {env in=["local","testing"]} ... {/env}
        //   {env not_in="local"} ... {/env}
        $smarty->registerPlugin('block', 'env', function ($params, $content, $smarty, &$repeat) {
            if (is_null($content)) {
                return;
            }

            $in = (array)($params['in'] ?? []);
            $not_in = (array)($params['not_in'] ?? []);
            $not_in = !empty($in) ? [] : $not_in;

            $env = \Rebet\Foundation\App::getEnv();
            if (in_array($env, $in)) {
                return $content;
            }

            if (!empty($not_in) && !in_array($env, $not_in)) {
                return $content;
            }

            return;
        });
    }
}
