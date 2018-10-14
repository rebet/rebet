<?php
namespace Rebet\Foundation\View\Engine\Twig;

use Rebet\Foundation\App;

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
    public static function customize(\Twig_Environment $twig) : void
    {
        // ------------------------------------------------
        // Check current environment
        // ------------------------------------------------
        // Params:
        //   $env : string|array - allow environments
        // Usage:
        //   {% if 'local' is env %} ... {% else %} ... {% endif %}
        //   {% if ['local','testing'] is env %} ... {% else% %} ... {% endif %}
        $twig->addTest(new \Twig_Test('env', function ($env) {
            return in_array(App::getEnv(), (array)$env);
        }));
    }
}
