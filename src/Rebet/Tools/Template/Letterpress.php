<?php
namespace Rebet\Tools\Template;

use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Tinker\Tinker;
use Rebet\Tools\Utility\Arrays;
use Rebet\Tools\Utility\Json;
use Rebet\Tools\Utility\Strings;

/**
 * Letterpress Class
 *
 * This class is simple template processor that supported Twig-like (actually not Twig) tag format.
 * Only following features are supported in the initial state.
 *
 *  - Comment      : {# comment #}
 *  - Placeholder  : {{ $var_with_sanitise }}, {! $var_without_sanitise !}
 *  - If statement : {% if expression %}...{% elseif expression %}...{% else %}...{% endif %}
 *  - For loop     : {% for $list as $k => $v %}...{% else %}...{% endfor %}
 *
 * This template wrap assigned vars by Tinker class when the variable with arrow operator or array accessor or used by 'for'.
 * So, you can use Tinker filter in template like below.
 *
 *  - {{ $entry_at->datetimef('Y/m/d H:i') }}
 *  - {{ $value->isInt() ? 'number' : 'other' }}
 *  - {% if $a->add($b)->gt(100) %}...
 *  - {% for $list->unique() as $value %}...
 *
 * And also you can use code like below,
 *
 *  - {{ $value ?? "default" }}            : In this case $value is not Tinker object, so `??` works as intended.
 *  - {{ $value->default("default") }}     : In this case $value is Tinker object, so if $value is null or undefined then 'default'.
 *  - {{ $value["key"] }}                  : In this case $value is Tinker object, so you can chain Tinker filter methods.
 *  - {{ $value->key }}                    : In this case $value is Tinker object, so you can chain Tinker filter methods.
 *  - {% if $can_edit %}...                : In this case $can_edit is not Tinker object, so `if` works as intended.
 *  - {% if $can_edit->default(true) %}... : In this case $can_edit is Tinker object, so if $can_edit is null or undefined then true.
 *  - {% for $list as $value %}...         : In this case $list is Tinker object, so works fine the `for` loop even if original $list is null or undefined.
 *  - {% if $a && $b %}...                 : In this case $a and $b are not Tinker object, so `if` works as intended.
 *  - {% if $a && $b->isInt() %}...        : In this case $a is not Tinker but $b is Tinker object, so $b->isInt() will return boolean (Tinker does not wrap return value by Tinker when the value is boolean), so `if` works as intended.
 *  - {% if $a->amount %}...               : In this case $a is Tinker, so $a->amount will return Tinker object, but 'if' peel Tinker object, so this code works as intended.
 *
 * But be careful
 *
 *  - {% if $a && $b->amount %}...         : In this case $a is not Tinker but $b is Tinker object, so $b->amount will return Tinker object (Tinker wrap return value by Tinker when the value is not boolean) and '&&' not peel Tinker object, so condition will true even if amount is zero.
 *
 * If you want to do this, you can write {% if $a && $b->amount->gt(0) %}.
 *
 * And all of tags are supporting '-' mark for whitespace control like Twig (but the behavior is not same as Twig).
 * The Letterpress '-' option has the ability,
 *
 *  - Row-oriented whitespace match: left '-' does not match LF following a blank, but right '-' matches LF following a blank.
 *  - Delete adjacent strings: '-' will delete adjacent strings or whitespace, '--' will delete adjacent strings followed by whitespace.
 *
 * So you can write like below,
 *
 *  - #{%-- if $a->is('a') -%}     => line will delete
 *  - $foo = 0/⋆{!- $foo --!}⋆/ ;  => $foo = 123; (⋆ means *)
 *
 * You can use this feature in combination with the comment symbol to embed Letterpress tags without violating the format of the embedded document.
 *
 * Usually it will be sufficient as a simple template if it has these features.
 * But sometimes we need tags that solve more complex issue, so this template support enhanced tags.
 *
 * 1. Letterpress::filter() : Register easily filter tag that will affect contents text.
 * ----------
 * Letterpress::filter('upper', function(string $contents){ return strtoupper($contents); });
 * => {% upper %}abc{% endupper %} become 'ABC'.
 *
 * Letterpress::filter('replace', function (string $contents, $pattern, $replacement, int $limit = -1) { return preg_replace($pattern, $replacement, $contents, $limit); });
 * => {% replace '/b/', 'B' %}abc{% endreplace %} become 'aBc'.
 *
 * 2. Letterpress::if() : Register easily condition tag like 'if'.
 * ----------
 * Letterpress::if('env', function (string ...$env) { return App::envIn(...$env); });
 * => {% env "development", "local" %}a{% elseenv "production" %}b{% else %}c{% endenv %} become 'a', 'b' or 'c' depend on environment.
 *
 * 3. Letterpress::function() : Register easily function tag that does not have contents.
 * ----------
 * Letterpress::function('welcome', function () { return "Welcome ".(Auth::user()->isGuest() ? 'to Rebet' : Auth::user()->name)."!"; });
 * => {% welcome %} become 'Welcome to Rebet!' or 'Welcome Username!' depend on user signin state.
 *
 * 4. Letterpress::block() : Register block type tag anything you want.
 * ----------
 * This method using syntax tree of Letterpress template, so you can do anything but little bit complexed.
 * Tag of 'for' also registered by this method.
 * NOTE: The nodes is passed a chunk block of consecutive tags defined in siblings.
 *
 * Letterpress::block('upper', null, function (array $nodes, array $vars) {
 *     foreach ($nodes as $node) {
 *        return strtoupper(Letterpress::process($node['nodes'], $vars));
 *     }
 * });
 * => {% upper %}abc{% endupper %} become 'ABC'.
 *
 * 5. Letterpress::embed() : Register embed type tag (that does not have contents) anything you want.
 * ----------
 * This method using syntax tree of Letterpress template, so you can do anything but little bit complexed.
 *
 * Letterpress::embed('hello', function (array $node, array $vars) { return trim("Hello ".Letterpress::evaluate($node['code'], $vars))."!"; });
 * => {% hello $name %} become 'Hello Rebet!' when $name is 'Rebet'.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Letterpress implements Renderable, \JsonSerializable
{
    /**
     * Tag type of `block`, that contains content like {% tag %}content{% endtag %}
     */
    protected const BLOCK_TAG = 'block';

    /**
     * Tag type of `embed`, that does not contains content like {% tag %}
     */
    protected const EMBED_TAG = 'embed';

    /**
     * Available tag set configuration.
     *
     * @var array
     */
    protected static $tag_set = [];

    /**
     * Open-tag stack.
     *
     * @var string[]
     */
    protected $open_tags = [];

    /**
     * Syntax tree of this text.
     *
     * @var array
     */
    protected $syntax = [];

    /**
     * Assigned variables
     *
     * @var array
     */
    protected $vars = [];

    /**
     * Create Letterpress instance.
     *
     * @param string|null $template
     * @throws LogicException when given template has syntax error
     */
    public function __construct(?string $template)
    {
        $this->syntax = $template ? $this->compile($template) : [] ;
    }

    /**
     * Create Letterpress instance.
     *
     * @param string|null $template
     * @throws LogicException when given template has syntax error
     * @return static
     */
    public static function of(?string $template) : self
    {
        return new static($template);
    }

    /**
     * Clear registered tag set configuration.
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$tag_set = [];
        static::init();
    }

    /**
     * It checks the given tag is already defined or not.
     *
     * @param string $tag
     * @return bool
     */
    public static function defined(string $tag) : bool
    {
        return isset(static::$tag_set[$tag])
            || in_array($tag, Arrays::flatten(Arrays::pluck(static::$tag_set, 'siblings')))
            ;
    }

    /**
     * Get unavailable siblings tags.
     *
     * @param array $siblings
     * @return bool
     */
    protected static function unavailableSiblings(array $siblings) : array
    {
        return Arrays::intersect(Arrays::unique(Arrays::flatten($siblings)), array_keys(static::$tag_set));
    }

    /**
     * Register block type `{% tag %} contents {% endtag %}` tag.
     *
     * @param string $tag name of open tag
     * @param array|null $siblings tag name [tag => [Tags that can be placed continuously], ...], when you want make if block then siblings should be `['if' => ['elseif', 'else'], 'elseif' => ['elseif', 'else'], 'else' => []]`.
     * @param \Closure $handler for tag processing `function(array $nodes, array $vars) : string { ... }`, The nodes is passed a chunk block of consecutive tags defined in siblings.
     * @return void
     */
    public static function block(string $tag, ?array $siblings, \Closure $handler) : void
    {
        if (static::defined($tag)) {
            throw new LogicException("Tag '{$tag}' is already defined.");
        }

        if (!empty($unavailables = static::unavailableSiblings($siblings ?? []))) {
            throw new LogicException("Tag '{$tag}' contains unavailable sibling tags [".implode(', ', $unavailables)."], these are already defined as tag.");
        }

        static::$tag_set[$tag] = [
            'type'     => static::BLOCK_TAG,
            'siblings' => $siblings ?? [$tag => []],
            'handler'  => $handler,
        ];
    }

    /**
     * Register embed type `{% tag %}` tag.
     *
     * @param string $tag name
     * @param \Closure $handler for tag processing `function(array $node, array $vars) : string { ... }`, The node is passed tag node ['tag' => '', 'code' => '', 'nodes' => []].
     * @return void
     */
    public static function embed(string $tag, \Closure $handler) : void
    {
        if (static::defined($tag)) {
            throw new LogicException("Tag '{$tag}' is already defined.");
        }

        static::$tag_set[$tag] = [
            'type'     => static::EMBED_TAG,
            'siblings' => [$tag => []],
            'handler'  => $handler,
        ];
    }

    /**
     * Register filter block type `{% tag options %} contents {% endtag %}` tag.
     *
     * Options can be write function arguments like `{% tag $arg1, $arg2, ... %}` then call `$filter($resolved_contents_text, $arg1, $arg2, ...)`
     * Options can be contained expression like `{% tag $arg1->isInt() ? 'a' : 'b', $arg2 %}` then call `$filter($resolved_contents_text, 'a' or 'b', $arg2)`
     * Options can be named parameter like `{% tag 'arg3' => 'a' %}` then call `$filter($resolved_contents_text, 1, 2, 'a')` if callback args has default value like `function($resolved_contents_text, $arg1 = 1, $arg2 = 2, $arg3 = 3)`
     *
     * @param string $tag
     * @param \Closure $filter function($resolved_contents_text, $options1, $options2, ...) : string { ... }
     * @return void
     */
    public static function filter(string $tag, \Closure $filter) : void
    {
        static::block(
            $tag,
            null,
            function (array $nodes, array $vars) use ($filter) {
                foreach ($nodes as $node) {
                    return Tinker::peel(Reflector::evaluate($filter, array_merge([Letterpress::process($node['nodes'], $vars)], Letterpress::evaluate('['.$node['code'].']', $vars))));
                }
                return '';
            }
        );
    }

    /**
     * Register `if` block tag and inverted tag like below,
     *  - `{% tag condition %} a {% elsetag condition %} b {% else %} c {% endtag %}` ,
     *  - `{% tagnot condition %} a {% elsetagnot condition %} b {% else %} c {% endtagnot %}`.
     *
     * Condition can be write function arguments like `{% tag $arg1, $arg2, ... %}` then call `$test($arg1, $arg2, ...)`
     * Condition can be contained expression like `{% tag $arg1->isInt() ? 'a' : 'b', $arg2 %}` then call `$test('a' or 'b', $arg2)`
     * Condition can be named parameter like `{% tag 'arg3' => 'a' %}` then call `$test(1, 2, 'a')` if callback args has default value like `function($arg1 = 1, $arg2 = 2, $arg3 = 3)`
     *
     * @param string $tag
     * @param \Closure $test function($condition_evaluated_value) { ... }
     * @return void
     */
    public static function if(string $tag, \Closure $test) : void
    {
        static::block(
            $tag,
            [$tag => ["else{$tag}", 'else'], "else{$tag}" => ["else{$tag}", 'else'], 'else' => []],
            function (array $nodes, array $vars) use ($test) {
                foreach ($nodes as $node) {
                    if ($node['tag'] === 'else' || Tinker::peel(Reflector::evaluate($test, Letterpress::evaluate('['.$node['code'].']', $vars)))) {
                        return Letterpress::process($node['nodes'], $vars);
                    }
                }
                return '';
            }
        );

        static::block(
            "{$tag}not",
            ["{$tag}not" => ["else{$tag}not", 'else'], "else{$tag}not" => ["else{$tag}not", 'else'], 'else' => []],
            function (array $nodes, array $vars) use ($test) {
                foreach ($nodes as $node) {
                    if ($node['tag'] === 'else' || !Tinker::peel(Reflector::evaluate($test, Letterpress::evaluate('['.$node['code'].']', $vars)))) {
                        return Letterpress::process($node['nodes'], $vars);
                    }
                }
                return '';
            }
        );
    }

    /**
     * Register function embed type `{% tag args %}` tag.
     *
     * Args can be write function arguments like `{% tag $arg1, $arg2, ... %}` then call `$callback($arg1, $arg2, ...)`
     * Args can be contained expression like `{% tag $arg1->isInt() ? 'a' : 'b', $arg2 %}` then call `$callback('a' or 'b', $arg2)`
     * Args can be named parameter like `{% tag 'arg3' => 'a' %}` then call `$callback(1, 2, 'a')` if callback args has default value like `function($arg1 = 1, $arg2 = 2, $arg3 = 3)`
     *
     * @param string $tag name
     * @param \Closure $callback `function(arg1, arg2, ...) : string { ... }`
     * @return void
     */
    public static function function(string $tag, \Closure $callback) : void
    {
        static::embed(
            $tag,
            function (array $node, array $vars) use ($callback) {
                return Tinker::peel(Reflector::evaluate($callback, Letterpress::evaluate('['.$node['code'].']', $vars)));
            }
        );
    }

    /**
     * Initialize built-in template tag set.
     *
     * @return void
     */
    public static function init()
    {
        // Define 'if' block tag
        static::if('if', function ($value) { return Tinker::peel($value); });

        // Define 'for' block tag
        static::block(
            'for',
            ['for' => ['else'], 'else' => []],
            function (array $nodes, array $vars) {
                $contents = '';
                foreach ($nodes as $node) {
                    if ($node['tag'] === 'else') {
                        return Letterpress::process($node['nodes'], $vars);
                    }

                    $vars['__callback'] = function ($vars) use (&$contents, $node) {
                        $vars      = Arrays::where($vars, function ($v, $k) { return !Strings::startsWith($k, '__'); });
                        $contents .= Letterpress::process($node['nodes'], $vars);
                    };
                    if (Letterpress::eval('$looped = false; foreach('.$node['code'].') { $looped = true; $__callback->invoke(compact(array_keys(get_defined_vars()))); }; return $looped;', $vars, false)) {
                        return $contents;
                    }
                }
                return $contents;
            }
        );
    }

    /**
     * Assign given vars.
     * This method merge own assigned vars by given vars.
     *
     * @param array $vars
     * @return self
     */
    public function with(array $vars) : self
    {
        $this->vars = array_merge($this->vars, $vars);
        return $this;
    }

    /**
     * Reset assigned vars.
     *
     * @return self
     */
    public function reset() : self
    {
        $this->vars = [];
        return $this;
    }

    /**
     * Render the template to string.
     *
     * @param string|null $template
     * @param array $vars (default: [])
     * @return string
     */
    public function render() : string
    {
        if (empty($this->syntax)) {
            return '';
        }
        return static::process($this->syntax, $this->vars);
    }

    /**
     * Compile the given template using tags configuration to syntax tree.
     *
     * @param string $template
     * @return array of syntax tree
     */
    protected function compile(string $template) : array
    {
        $this->open_tags = [];
        $root            = ['tag' => '', 'code' => '', 'nodes' => []];
        $leftovers       = $this->removeComments($template);
        while (!empty($leftovers)) {
            [$root, $leftovers] = $this->parse($leftovers, $root);
        }
        if (!empty($this->open_tags)) {
            throw new LogicException("Missing close tag {% end".end($this->open_tags)." %}, reached end of template text.");
        }
        return $root['nodes'];
    }

    /**
     * Parse leftovers that unanalyzed part of template.
     *
     * @param string $leftovers that unanalyzed part of template
     * @param array $parent node
     * @return array of partial syntax tree
     */
    protected function parse(string $leftovers, array $parent) : array
    {
        $prev = null;
        if (!empty($parent['nodes'])) {
            $prev = &$parent['nodes'][count($parent['nodes']) - 1];
            if ($prev === null || !is_array($prev) || (static::$tag_set[$prev['tag']]['type'] ?? null) === static::EMBED_TAG) {
                $prev = null;
            }
        }

        while (true) {
            [$content, $tag, $code, $leftovers] = $this->next($leftovers);

            // Remove close or embed tag mark(=null) if exists, then add contents
            if ($prev) {
                if (end($prev['nodes']) === null) {
                    array_pop($prev['nodes']);
                }
                $prev['nodes'][] = $content;
            } else {
                if (end($parent['nodes']) === null) {
                    array_pop($parent['nodes']);
                }
                $parent['nodes'][] = $content;
            }

            if (!empty($tag)) {
                $prev_tag = $prev['tag'] ?? $parent['tag'] ?? null;

                // Handle close tag.
                if (Strings::startsWith($tag, 'end') && !array_key_exists($tag, static::$tag_set)) {
                    $close_tag = 'end'.array_pop($this->open_tags);
                    if ($tag !== $close_tag) {
                        throw new LogicException(
                            $close_tag === 'end'
                        ? "Missing open tag {% ".Strings::ltrim($tag, 'end')." %} , {% {$tag} %} found."
                        : "Missing close tag {% {$close_tag} %}, {% {$tag} %} found."
                        );
                    }

                    if (!empty($leftovers)) {
                        $parent['nodes'][] = null; // Set close tag mark(=null)
                    }
                    return [$parent, $leftovers];
                }

                // Handle embed tag.
                if ((static::$tag_set[$tag]['type'] ?? null) === static::EMBED_TAG) {
                    if ($prev) {
                        $prev['nodes'][]   = ['tag' => $tag, 'code' => $code, 'nodes' => []];
                    } else {
                        $parent['nodes'][] = ['tag' => $tag, 'code' => $code, 'nodes' => []];
                    }
                    continue;
                }

                // Handle sibling tags of open tag.
                if (in_array($tag, static::$tag_set[end($this->open_tags)]['siblings'][$prev_tag] ?? [])) {
                    $parent['nodes'][] = ['tag' => $tag, 'code' => $code, 'nodes' => []];
                    return $this->parse($leftovers, $parent);
                }

                if (empty(static::$tag_set[$tag])) {
                    throw new LogicException("Unsupported (or invalid position) tag {% {$tag} %} found.");
                }

                // Handle block tags
                if (!$prev) {
                    $prev = &$parent;
                }
                $this->open_tags[]  = $tag;
                $prev['nodes'][]    = ['tag' => $tag, 'code' => $code, 'nodes' => []];
                [$prev, $leftovers] = $this->parse($leftovers, $prev);
                return [$parent, $leftovers];
            }

            break;
        };

        return [$parent, $leftovers];
    }

    /**
     * Remove comments
     *
     * @param string $template
     * @return string
     */
    protected function removeComments(string $template) : string
    {
        return preg_replace('/([ \f\r\t]*\S*{#--|([ \f\r\t]*|\S+){#-|{#)([\s\S]*)(#}|-#}(\S+?|([ \f\r\t]*\n|[ \f\r\t]*?))|--#}\S*?([ \f\r\t]*\n|[ \f\r\t]*?))/Uu', '', $template);
    }

    /**
     * Get next tag
     *
     * @param string $leftovers
     * @return string[] [content, tag, code, leftovers]
     */
    protected function next(string $leftovers) : array
    {
        if (preg_match('/^(?<content>[\s\S]*)([ \f\r\t]*?\S*?{%--|([ \f\r\t]*?|\S+?){%-|{%)[\s]*(?<tag>[^\s\-}]+?)(?<code>[\s\S]*)(%}|-%}(\S+?|([ \f\r\t]*\n|[ \f\r\t]*?))|--%}\S*?([ \f\r\t]*\n|[ \f\r\t]*?))(?<leftovers>[\s\S]*)$/Uu', $leftovers, $matches)) {
            return [$matches['content'], $matches['tag'], trim($matches['code'] ?? ''), $matches['leftovers']];
        }
        return [$leftovers, null, null, null];
    }

    /**
     * Process syntax tree node using given vars context and tags configuration.
     *
     * @param array $nodes of process target
     * @param array $vars of current context
     * @return string of partial result text
     */
    public static function process(array $nodes, array $vars) : string
    {
        $contents = '';
        $family   = [];
        $siblings = [];
        $open_tag = null;
        $prev_tag = null;
        foreach ($nodes as $node) {
            // Process contents node.
            if (is_string($node)) {
                // Process block node first if block (and siblings) node stacked.
                if (!empty($family)) {
                    $contents .= call_user_func(static::$tag_set[$open_tag]['handler'], $family, $vars);
                    $family    = [];
                }

                $contents .= static::expandVars($node, $vars);
                continue;
            }

            $tag = $node['tag'];

            // Process embed node.
            if ((static::$tag_set[$tag]['type'] ?? null) === static::EMBED_TAG) {
                // Process block node first if block (and siblings) node stacked.
                if (!empty($family)) {
                    $contents .= call_user_func(static::$tag_set[$open_tag]['handler'], $family, $vars);
                    $family    = [];
                }

                $contents .= call_user_func(static::$tag_set[$tag]['handler'], $node, $vars);
                continue;
            }

            if (!empty($family)) {
                // Stack block siblings node.
                if (in_array($tag, $siblings[$prev_tag] ?? [])) {
                    $prev_tag = $tag;
                    $family[] = $node;
                    continue;
                }

                // Process block node.
                $contents .= call_user_func(static::$tag_set[$open_tag]['handler'], $family, $vars);
            }

            // Start block node.
            $open_tag = $tag;
            $prev_tag = $tag;
            $siblings = static::$tag_set[$open_tag]['siblings'];
            $family   = [$node];
        }

        // Process block node if block (and siblings) node stacked.
        if (!empty($family)) {
            $contents .= call_user_func(static::$tag_set[$open_tag]['handler'], $family, $vars);
        }

        return $contents;
    }

    /**
     * Optimize vars for given code like below,
     * - Init undefined variables with null.
     * - Not wrap in Tinker if the value is used as an object with no operations.
     * - Wrap in Tinker if the value is used as an object with operations. (this is prioritized when value using both way)
     *
     * @param string $code
     * @param array $vars
     * @param bool $alone_var_without_tinker (default: true)
     * @return array
     */
    public static function optimizeVars(string $code, array $vars, bool $alone_var_without_tinker = true) : array
    {
        if (preg_match_all('/(\$(?<accompanies>[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)(?=->|\[))|(\$(?<alones>[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*))/u', $code, $matches)) {
            foreach (Arrays::compact($matches['alones']) as $name) {
                $vars[$name] = $alone_var_without_tinker
                    ? (isset($vars[$name]) ? Tinker::peel($vars[$name]) : null)
                    : Tinker::with(isset($vars[$name]) ? $vars[$name] : null)
                    ;
            }
            foreach (Arrays::compact($matches['accompanies']) as $name) {
                $vars[$name] = Tinker::with(isset($vars[$name]) ? $vars[$name] : null) ;
            }
        }
        return $vars;
    }

    /**
     * Expand placeholder in template using given vars.
     *
     * @param string $template
     * @param array|Tinker $vars
     * @return string
     * @throws LogicException when placeholder format is invalid.
     */
    public static function expandVars(string $template, $vars) : string
    {
        return preg_replace_callback('/([ \f\r\t]*?\S*?{(?<so1>[{!])--|([ \f\r\t]*?|\S+?){(?<so2>[{!])-|{(?<so3>[{!]))(?<code>[\s\S]*)((?<sc1>[!}])}|-(?<sc2>[!}])}(\S+?|([ \f\r\t]*\n|[ \f\r\t]*?))|--(?<sc3>[!}])}\S*?([ \f\r\t]*\n|[ \f\r\t]*?))/Uu', function ($matches) use ($vars) {
            $sanitise_open  = $matches['so1'] ?: $matches['so2'] ?: $matches['so3'];
            $sanitise_close = $matches['sc1'] ?: $matches['sc2'] ?: $matches['sc3'];
            if (($sanitise_open === '!') xor ($sanitise_close === '!')) {
                throw new LogicException("Invalid placeholder format '{{$sanitise_open} ... {$sanitise_close}}' found.");
            }
            $value = static::evaluate(trim($matches['code']), $vars);
            return $sanitise_open === '!' ? $value : htmlentities($value, ENT_QUOTES, 'UTF-8', false) ;
        }, $template);
    }

    /**
     * Execute PHP partial expression code.
     *
     * @param string $__code
     * @param array|Tinker $__vars
     * @param bool $__alone_var_without_tinker (default: true)
     * @return mixed
     */
    public static function evaluate(string $__code, $__vars, bool $__alone_var_without_tinker = true)
    {
        return empty($__code) ? null : static::eval("return ({$__code});", $__vars, $__alone_var_without_tinker);
    }

    /**
     * Execute PHP partial code.
     *
     * @param string $__code
     * @param array|Tinker $__vars
     * @param bool $__alone_var_without_tinker (default: true)
     * @return mixed
     */
    public static function eval(string $__code, $__vars, bool $__alone_var_without_tinker = true)
    {
        if (empty($__code)) {
            return;
        }
        $__vars = static::optimizeVars($__code, $__vars, $__alone_var_without_tinker);
        foreach ($__vars as $__name => $__value) {
            ${$__name} = $__value;
        }
        return Tinker::peel(eval($__code));
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return Json::serialize($this->vars);
    }
}

Letterpress::init();
