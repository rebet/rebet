<?php
namespace Rebet\Tools\Template;

use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Tinker\Tinker;
use Rebet\Tools\Utility\Arrays;
use Rebet\Tools\Utility\Json;
use Rebet\Tools\Utility\Strings;

/**
 * Text Class
 *
 * This class is simple template processor that supported Twig-like tag format.
 * Only following features are supported in the initial state.
 * NOTE: All of tags are supporting '-' mark for whitespace control like Twig.
 * NOTE: HTML is not main target for this template, so placeholder do not sanitise defaultly.
 *
 *  - Comment      : {# comment #}
 *  - Plaseholder  : {{ $var }}
 *  - If statement : {% if expression %}...{% elseif expression %}...{% else %}...{% endif %}
 *  - For loop     : {% for $list as $k => $v %}...{% else %}...{% endfor %}
 *
 * This template wrap assigned vars by Tinker class.
 * So, you can use Tinker filter in template like below.
 *
 *  - {{ $entry_at->datetimef('Y/m/d H:i') }}
 *  - {{ $value->isInt() ? 'number' : 'other' }}
 *  - {% if $a->add($b)->gt(100) %}...
 *  - {% for $list->unique() as $value %}...
 *
 * Usually it will be sufficient as a simple template if it has these features.
 * But sometimes we need tags that solve more complex issue, so this template support enhanced tags.
 *
 * 1. Text::filter() : Register easily filter tag that will affect contents text.
 * ----------
 * Text::filter('upper', function(string $contents){ return strtoupper($contents); });
 * => {% upper %}abc{% endupper %} become 'ABC'.
 *
 * Text::filter('replace', function (string $contents, $pattern, $replacement, int $limit = -1) { return preg_replace($pattern, $replacement, $contents, $limit); });
 * => {% replace '/b/', 'B' %}abc{% endreplace %} become 'aBc'.
 *
 * 2. Text::if() : Register easily condition tag like 'if'.
 * ----------
 * Text::if('env', function (string ...$env) { return App::envIn(...$env); });
 * => {% env "development", "local" %}a{% elseenv "production" %}b{% else %}c{% endenv %} become 'a', 'b' or 'c' depend on environment.
 *
 * 3. Text::function() : Register easily function tag that does not have contents.
 * ----------
 * Text::function('welcome', function () { return "Welcome ".(Auth::user()->isGuest() ? 'to Rebet' : Auth::user()->name)."!"; });
 * => {% welcome %} become 'Welcome to Rebet!' or 'Welcome Username!' depend on user signin state.
 *
 * 4. Text::block() : Register block type tag anything you want.
 * ----------
 * This method using syntax tree of Text template, so you can do anything but little bit complexed.
 * Tag of 'for' also registered by this method.
 * NOTE: The nodes is passed a chunk block of consecutive tags defined in siblings.
 *
 * Text::block('upper', null, function (array $nodes, array $vars) {
 *     foreach ($nodes as $node) {
 *        return strtoupper(Text::process($node['nodes'], $vars));
 *     }
 * });
 * => {% upper %}abc{% endupper %} become 'ABC'.
 *
 * 5. Text::embed() : Register embed type tag (that does not have contents) anything you want.
 * ----------
 * This method using syntax tree of Text template, so you can do anything but little bit complexed.
 *
 * Text::embed('hello', function (array $node, array $vars) { return trim("Hello ".Text::evaluate($node['code'], $vars))."!"; });
 * => {% hello $name %} become 'Hello Rebet!' when $name is 'Rebet'.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Text implements Renderable, \JsonSerializable
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
     * Create Text instance.
     *
     * @param string|null $template
     * @throws LogicException when given template has syntax error
     */
    public function __construct(?string $template)
    {
        $this->syntax = $template ? $this->compile($template) : [] ;
    }

    /**
     * Create Text instance.
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
                    return Tinker::peel(Reflector::evaluate($filter, array_merge([Text::process($node['nodes'], $vars)], Text::evaluate('['.$node['code'].']', $vars))));
                }
                return '';
            }
        );
    }

    /**
     * Register if block type `{% tag condition %} a {% elsetag condition %} b {% else %} c {% endtag %}` tag.
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
                    if ($node['tag'] === 'else' || Tinker::peel(Reflector::evaluate($test, Text::evaluate('['.$node['code'].']', $vars)))) {
                        return Text::process($node['nodes'], $vars);
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
                return Tinker::peel(Reflector::evaluate($callback, Text::evaluate('['.$node['code'].']', $vars)));
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
        static::if('if', function ($value) { return $value; });

        // Define 'for' block tag
        static::block(
            'for',
            ['for' => ['else'], 'else' => []],
            function (array $nodes, array $vars) {
                $contents = '';
                foreach ($nodes as $node) {
                    if ($node['tag'] === 'else') {
                        return Text::process($node['nodes'], $vars);
                    }

                    $vars['__callback'] = function ($vars) use (&$contents, $node) {
                        $vars      = Arrays::where($vars, function ($v, $k) { return !Strings::startsWith($k, '__'); });
                        $contents .= Text::process($node['nodes'], $vars);
                    };
                    if (Text::eval('$looped = false; foreach('.$node['code'].') { $looped = true; $__callback->invoke(compact(array_keys(get_defined_vars()))); }; return $looped;', $vars)) {
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

                    $parent['nodes'][] = null; // Set close tag mark(=null)
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
        return preg_replace('/([ \f\r\t]*{#-|{#)([\s\S]*)(#}|-#}([ \f\r\t]*\n|[ \f\r\t]*?))/Uu', '', $template);
    }

    /**
     * Get next tag
     *
     * @param string $leftovers
     * @return string[] [content, tag, code, leftovers]
     */
    protected function next(string $leftovers) : array
    {
        if (preg_match('/^(?<content>[\s\S]*)([ \f\r\t]*?{%-|{%)[\s]*(?<tag>[^\s\-}]+?)(?<code>[\s\S]*)(%}|-%}([ \f\r\t]*\n|[ \f\r\t]*?))(?<leftovers>[\s\S]*)$/Uu', $leftovers, $matches)) {
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
     * Init undefined variables with null.
     *
     * @param string $code
     * @param array $vars
     * @return array
     */
    public static function undefinedVarsCompletion(string $code, array $vars) : array
    {
        if (preg_match_all('/\$(?<candidates>[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)/u', $code, $matches)) {
            foreach ($matches['candidates'] as $candidate) {
                if (!isset($vars[$candidate])) {
                    $vars[$candidate] = null;
                }
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
     */
    public static function expandVars(string $template, $vars) : string
    {
        return preg_replace_callback('/(\s{{-|{{)(?<code>[\s\S]*)(}}|-}}\s)/Uu', function ($matches) use ($vars) {
            return static::evaluate(trim($matches['code']), $vars);
        }, $template);
    }

    /**
     * Execute PHP partial expression code.
     *
     * @param string $__code
     * @param array|Tinker $__vars
     * @return mixed
     */
    public static function evaluate(string $__code, $__vars)
    {
        return empty($__code) ? null : static::eval("return ({$__code});", $__vars);
    }

    /**
     * Execute PHP partial code.
     *
     * @param string $__code
     * @param array|Tinker $__vars
     * @return mixed
     */
    public static function eval(string $__code, $__vars)
    {
        if (empty($__code)) {
            return;
        }
        $__vars = Tinker::with(static::undefinedVarsCompletion($__code, $__vars));
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

Text::init();
