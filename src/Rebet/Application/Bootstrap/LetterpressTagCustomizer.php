<?php
namespace Rebet\Application\Bootstrap;

use Rebet\Application\Http\HttpKernel;
use Rebet\Application\Kernel;
use Rebet\Application\View\Tag\BuiltinTagProcessors;
use Rebet\Tools\Template\Letterpress;
use Rebet\Tools\Tinker\Tinker;

/**
 * Letterpress Tag Customizer Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LetterpressTagCustomizer implements Bootstrapper
{
    /**
     * {@inheritDoc}
     */
    public function bootstrap(Kernel $kernel)
    {
        // ------------------------------------------------
        // [env/envnot] Check current environment
        // ------------------------------------------------
        // Params:
        //   $env : string|string[] - allow enviroments
        // Usage:
        //   {% env 'local' %} ... {% elseenv 'testing' %} ... {% else %} ... {% endenv %}
        //   {% env 'local', 'testing' %} ... {% else %} ... {% endenv %}
        $processor = BuiltinTagProcessors::env();
        Letterpress::if('env', function (string ...$env) use ($processor) {
            return $processor->execute($env);
        });

        // ------------------------------------------------
        // [prefix] Output route prefix
        // ------------------------------------------------
        // Params:
        //   (none)
        // Usage:
        //   {% prefix %}
        Letterpress::function('prefix', function () use ($kernel) {
            return $kernel instanceof HttpKernel ? $kernel->request()->getRoutePrefix() : '' ;
        });

        // ------------------------------------------------
        // [role/rolenot] Check current users role (Authorization)
        // ------------------------------------------------
        // Params:
        //   $roles : string|string[] - role names
        // Usage:
        //   {% role 'admin' %} ... {% elserole 'user' %} ... {% else %} ... {% endrole %}
        //   {% role 'user', 'guest' %} ... {% else %} ... {% endrole %}
        //   {% role 'user', 'guest:post-editable' %} ... {% else %} ... {% endrole %}
        $processor = BuiltinTagProcessors::role();
        Letterpress::if('role', function (string ...$roles) use ($processor) {
            return $processor->execute($roles);
        });

        // ------------------------------------------------
        // [can/cannot] Check policy for target to current user (Authorization)
        // ------------------------------------------------
        // Params:
        //   $action : string        - action name
        //   $target : string|object - target object or class or any name
        //   $extras : mixed|mixed[] - extra arguments
        // Usage:
        //   {% can 'update', $post %} ... {% elsecan 'create', Post::class %} ... {% else %} ... {% endcan %}
        //   {% can 'create', Post::class %} ... {% else %} ... {% endcan %}
        //   {% can 'update', 'remark', $post %} ... {% else %} ... {% endcan %}
        $processor = BuiltinTagProcessors::can();
        Letterpress::if('can', function (string $action, $target, ...$extras) use ($processor) {
            return $processor->execute([$action, Tinker::peel($target), ...Tinker::peelAll($extras)]);
        });

        // ------------------------------------------------
        // [lang] Translate given message to current locale
        // ------------------------------------------------
        // Params:
        //   $key         : string - translate message key.
        //   $replacement : array  - parameter of translate message. (default: [])
        //   $selector    : mixed  - translation message selector for example pluralize. (default: null)
        //   $locale      : string - locale that translate to. (default: null for current locale)
        // Usage:
        //   {% 'messages.welcome' %}
        //   {% 'messages.welcome', ['name' => 'Jhon'] %}
        //   {% 'messages.welcome', $replacements %}
        //   {% 'messages.welcome', 'locale' => 'en' %}
        //   {% 'messages.tags', ['tags' => $tags], count($tags) %}
        //   {% 'messages.tags', ['tags' => $tags], count($tags), 'en' %}
        $processor = BuiltinTagProcessors::lang();
        Letterpress::function('lang', function (string $key, $replacement = [], $selector = null, ?string $locale = null) use ($processor) {
            return $processor->execute([$key, is_array($replacement) ? Tinker::peelAll($replacement) : Tinker::peel($replacement), Tinker::peel($selector), $locale]);
        });
    }
}
