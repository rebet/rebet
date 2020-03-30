<?php
namespace Rebet\Foundation\View\Engine\Twig;

use Rebet\Common\Path;
use Rebet\Database\Pagination\Paginator;
use Rebet\Foundation\View\Engine\BuiltinTagProcessors;
use Rebet\Http\Request;
use Rebet\Translation\FileDictionary;
use Rebet\Translation\Translator;
use Rebet\View\Engine\Twig\Twig;

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
    public static function customize(Twig $twig) : void
    {
        $twig->appendPath(__DIR__.'/views');
        $environment = $twig->core();

        // ------------------------------------------------
        // [env is/env is not] Check current environment
        // ------------------------------------------------
        // Params:
        //   $env : string|array - allow enviroments
        // Usage:
        //   {% env is 'local' %} ... {% elseenv is 'testing' %} ... {% else %} ... {% endenv %}
        //   {% env is 'local', 'testing' %} ... {% else %} ... {% endenv %}
        //   {% env is 'local' or 'testing' %} ... {% else %} ... {% endenv %}
        //   {% env is 'local', 'testing' or 'production' %} ... {% else %} ... {% endenv %}
        $environment->case('env', 'is', ['...' => [',', 'or']], BuiltinTagProcessors::env());

        // ------------------------------------------------
        // [prefix] Output route prefix
        // ------------------------------------------------
        // Params:
        //   (none)
        // Usage:
        //   {% prefix %}
        $environment->embed('prefix', null, null, 'echo(', BuiltinTagProcessors::prefix(), ');', ['prefix']);

        // ------------------------------------------------
        // [role is/role is not] Check current users role (Authorization)
        // ------------------------------------------------
        // Params:
        //   $roles : string - role names
        // Usage:
        //   {% role is 'admin' %} ... {% elserole is 'user' %} ... {% else %} ... {% endrole %}
        //   {% role is 'user', 'guest' %} ... {% else %} ... {% endrole %}
        //   {% role is 'user' or 'guest' %} ... {% else %} ... {% endrole %}
        //   {% role is 'user', 'guest:post-editable' %} ... {% else %} ... {% endrole %}
        $environment->case('role', 'is', ['...' => [',', 'or']], BuiltinTagProcessors::role());

        // ------------------------------------------------
        // [can/can not] Check policy for target to current user (Authorization)
        // ------------------------------------------------
        // Params:
        //   $action : string        - action name
        //   $target : string|object - target object or class or any name
        //   $extras : mixed         - extra arguments
        // Usage:
        //   {% can 'update' $post %} ... {% elsecan 'create' Post::class %} ... {% else %} ... {% endcan %}
        //   {% can 'create' Post::class %} ... {% else %} ... {% endcan %}
        //   {% can 'update' 'remark' with $post %} ... {% else %} ... {% endcan %}
        //   {% can 'update' $post with $a, $b and $c %} ... {% else %} ... {% endcan %}
        $environment->case('can', '', ['', 'with', '...' => [',', 'and']], BuiltinTagProcessors::can());

        // ------------------------------------------------
        // [field] Bind field attribute name
        // ------------------------------------------------
        // Params:
        //   $name : string - attribute name
        // Usage: <bind field name>
        //   {% field 'email' %} ... {% endfield %}
        // Usage: <In {% field %} block>
        //   {% field %}
        // Note:
        //   It does not correspond to nesting.
        $environment->embed('field', '', [], 'echo(', BuiltinTagProcessors::field(), ');');
        $environment->embed('endfield', '', null, '', BuiltinTagProcessors::endfield(), ';');

        // ------------------------------------------------
        // [error] Output error message of given attributes
        // ------------------------------------------------
        // Params:
        //   $names : string|array - attribute names (default: {% field %} if exists, otherwise '*')
        //   $outer : string       - outer text/html template with :messages placeholder (default: @errors.outer in /i18n/message.php)
        //   $inner : string       - inner text/html template with :message placeholder (default: @errors.inner in /i18n/message.php)
        // Usage:
        //   {% error %}
        //   {% error 'email' %}
        //   {% error ['first_name', 'last_name'] %}
        //   {% error '*' %}
        //   {% error 'email' format '<div class="error">:messages</div>' %}
        //   {% error 'email' format '<div class="error">:messages</div>', '* :message<br>' %}
        //   {% error 'email' format inner='* :message<br>' %}
        // Usage: <In {% field %} block>
        //   {% error %}
        //   {% error format '<div class="errors"><ul class="error">:messages</ul></div>' %}
        //   {% error format '<div class="error">:messages</div>', '* :message<br>' %}
        //   {% error format inner='* :message<br>' %}
        $environment->embed('error', '', ['format', ','], 'echo(', BuiltinTagProcessors::error(), ');', ['errors'], true);

        // ------------------------------------------------
        // [errors/errors not] Check error is exists
        // ------------------------------------------------
        // Params:
        //   $name : string - attribute name (default: null)
        // Usage:
        //   {% errors %} ... {% else %} ... {% enderrors %}
        //   {% errors 'email' %} ... {% else %} ... {% enderrors %}
        // Usage: <In {% field %} block>
        //   {% errors %} ... {% else %} ... {% enderrors %}
        $environment->case('errors', '', [], BuiltinTagProcessors::errors(), ['errors'], true);

        // ------------------------------------------------
        // [iferror] Output given value if error
        // ------------------------------------------------
        // Params:
        //   $name : string - attribute names
        //   $then : mixed  - return value if error is exists
        //   $else : mixed  - return value if error is not exists (default: '')
        // Usage:
        //   {% iferror 'email' then 'color: red;' %}
        //   {% iferror 'email' then 'color: red;' else 'color: gleen;' %}
        //   {% iferror 'email' ? 'color: red;' : 'color: gleen;' %}
        // Usage: <In {% field %} block>
        //   {% iferror then 'color: red;' %}
        //   {% iferror then 'color: red;' else 'color: gleen;' %}
        //   {% iferror ? 'color: red;' : 'color: gleen;' %}
        $environment->embed('iferror', '', [['then', '?'], ['else', ':']], 'echo(', BuiltinTagProcessors::iferror(), ');', ['errors'], true);

        // ------------------------------------------------
        // [e] Output error grammers if error
        // ------------------------------------------------
        // Params:
        //   $name    : string - attribute name (default: @field if exists)
        //   $grammer : string - glammer name of @errors in /i18n/message.php.
        // Usage:
        //   {% e 'email' 'class' %}
        //   {% e 'email' 'icon' %}
        // Usage: <In {% field %} block>
        //   {% e 'class' %}
        //   {% e 'icon' %}
        $environment->embed('e', '', [''], 'echo(', BuiltinTagProcessors::e(), ');', ['errors'], true);

        // ------------------------------------------------
        // [input] Output input data
        // ------------------------------------------------
        // Params:
        //   $name    : string - attribute name
        //   $default : mixed  - default valule (default: '')
        // Usage:
        //   {% input 'email' %}
        //   {% input 'email' default $user->email %}
        //   {% input 'email' ?? $user->email %}
        // Usage: <In {% field %} block>
        //   {% input %}
        //   {% input default $user->email %}
        //   {% input ?? $user->email %}
        $environment->embed('input', '', [['default', '??']], 'echo(', BuiltinTagProcessors::input(), ');', ['input'], true);

        // ------------------------------------------------
        // [csrf_token] Output csrf token value
        // ------------------------------------------------
        // Params:
        //   $scope : mixed - if the scope is given then token become one time token.
        // Usage:
        //   {% csrf_token %}
        //   {% csrf_token for 'user', 'edit' %}
        //   {% csrf_token for 'article', 'edit', article.article_id %}
        $environment->embed('csrf_token', 'for', ['...' => [',', 'and']], 'echo(', BuiltinTagProcessors::csrfToken(), ');');

        // ------------------------------------------------
        // [csrf] Output csrf token hidden field tag
        // ------------------------------------------------
        // Params:
        //   $scope : mixed - if the scope is given then token become one time token.
        // Usage:
        //   {% csrf %}
        //   {% csrf for 'user', 'edit' %}
        //   {% csrf for 'article', 'edit', article.article_id %}
        $environment->embed('csrf', 'for', ['...' => [',', 'and']], 'echo(', BuiltinTagProcessors::csrf(), ');');

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
        //   {% lang 'messages.welcome' with {'name': 'Jhon'} %}
        //   {% lang 'messages.tags' with {'tags': tags} for count(tags) %}
        //   {% lang 'messages.tags' with {'tags': tags} for count(tags), 'en' %}
        //   {% lang 'messages.tags' with {'tags': tags} for locale='en' %}
        $environment->embed('lang', '', ['with', 'for', ','], 'echo(', BuiltinTagProcessors::lang(), ');');

        // ------------------------------------------------
        // [paginate] Pagination link output tag
        // ------------------------------------------------
        // Params:
        //   $paginator : Paginator     - the paginator object.
        //   $options   : array         - pagination options.
        //     - template : string|null - the template name of pagination. (default: null for use default template)
        //     - action   : string|null - the paginate link action url. (default: null for use Request::getRequestPath())
        //     - query    : array|null  - the paginate link query parameters. (default: null for use Request::input())
        //     - append   : array|null  - the appended paginate link query parameters that append/override 'query' parameters. (default: null)
        //     - reject   : array|null  - the rejected link query parameter keys from 'query' parameters. (default: null)
        //     - *        : mixed       - Other options will pass through to paginate template, as it is.
        // Usage:
        //   {% paginate of users %}
        //   {% paginate of users that {'template': 'paginate@semantic-ui'} %}
        // Note:
        //   Default paginate template can be changed by Rebet\Foundation\App.paginate.default_template configure.
        $environment->embed('paginate', 'of', ['that'], 'echo(', BuiltinTagProcessors::paginate(), ');');
    }
}


// ---------------------------------------------------------
// Add library default translation resource
// ---------------------------------------------------------
Translator::addResourceTo(FileDictionary::class, Path::normalize(__DIR__.'/i18n'), 'pagination');
