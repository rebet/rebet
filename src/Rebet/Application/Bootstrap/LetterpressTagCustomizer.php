<?php

declare(strict_types=1);

namespace Rebet\Application\Bootstrap;

use Rebet\Application\Http\WebKernel;
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
    public function bootstrap(Kernel $kernel): void
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
        Letterpress::if('env', fn(string ...$env) => $processor->execute($env));

        // ------------------------------------------------
        // [prefix] Output route prefix
        // ------------------------------------------------
        // Params:
        //   (none)
        // Usage:
        //   {% prefix %}
        Letterpress::function('prefix', fn() => $kernel instanceof WebKernel ? $kernel->request()->getRoutePrefix() : '');

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
        Letterpress::if('role', fn(string ...$roles) => $processor->execute($roles));

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
        Letterpress::if('can', fn(string $action, $target, ...$extras) => $processor->execute([$action, Tinker::peel($target), ...Tinker::peelAll($extras)]));

        // ------------------------------------------------
        // [lang] Translate given message to current locale
        // ------------------------------------------------
        // Params:
        //   $key         : string - translate message key.
        //   $replacement : array  - parameter of translate message. (default: [])
        //   $selector    : mixed  - translation message selector for example pluralize. (default: null)
        //   $locale      : string - locale that translate to. (default: null for current locale)
        // Usage:
        //   {% lang 'messages.welcome' %}
        //   {% lang 'messages.welcome', ['name' => 'Jhon'] %}
        //   {% lang 'messages.welcome', $replacements %}
        //   {% lang 'messages.welcome', 'locale' => 'en' %}
        //   {% lang 'messages.tags', ['tags' => $tags], count($tags) %}
        //   {% lang 'messages.tags', ['tags' => $tags], count($tags), 'en' %}
        $processor = BuiltinTagProcessors::lang();
        Letterpress::function('lang', fn(string $key, $replacement = [], $selector = null, string|null $locale = null) => $processor->execute([$key, is_array($replacement) ? Tinker::peelAll($replacement) : Tinker::peel($replacement), Tinker::peel($selector), $locale]));
    }
}
