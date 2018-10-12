<?php

/**
 * env check smarty plugin for Rebet
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
function smarty_block_env($params, $content, Smarty_Internal_Template &$smarty, &$repeat)
{
    if (is_null($content)) {
        return;
    }
    
    $in     = (array)($params['in']     ?? []) ;
    $not_in = (array)($params['not_in'] ?? []) ;
    $not_in = !empty($in) ? [] : $not_in ;
    
    $env = \Rebet\Foundation\App::getEnv();
    if (in_array($env, $in)) {
        return $content;
    }
    
    if (!empty($not_in) && !in_array($env, $not_in)) {
        return $content;
    }
    
    return;
}
