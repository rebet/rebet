<?php
namespace Rebet\Common;

use Rebet\Common\Exception\LogicException;
use Rebet\Config\Configurable;
use Rebet\Stream\Stream;

/**
 * Template Class
 *
 * This class is simple template processor.
 *
 * @see https://github.com/shigeru-kuratani/SimpleTemplate/blob/master/SimpleTemplate.class.php
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Template
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'tags' => [
                'if'  => [
                    'type'     => Template::BLOCK_TAG,
                    'siblings' => ['if' => ['elseif', 'else'], 'elseif' => ['elseif', 'else'], 'else' => []],
                    'handler'  => function (array $nodes, array $vars, array $tags) {
                        foreach ($nodes as [$tag, $code, $children]) {
                            if ($tag === 'else' || Template::eval($code, $vars)) {
                                return Template::process($children, $vars, $tags);
                            }
                        }
                        return '';
                    }
                ],
                'for' => [
                    'type'     => Template::BLOCK_TAG,
                    'siblings' => ['foreach' => []],
                    'handler'  => function (array $nodes, array $vars, array $tags) {
                        $contents = '';
                        foreach ($nodes as [$tag, $code, $children]) {
                            $vars['__callback'] = function ($vars) use (&$contents, $children, $tags) {
                                $contents .= Template::process($children, $vars, $tags);
                            };
                            Template::eval('foreach('.$code.') { $__callback(compact()); }', $vars);
                        }
                        return $contents;
                    }
                ],
            ],
        ];
    }

    const BLOCK_TAG    = 'block';
    const FUNCTION_TAG = 'function';

    protected $open_tags = [];

    public function render(?string $template, array $vars = []) : string
    {
        if (Utils::isBlank($template)) {
            return '';
        }
        $tags = static::config('tags', false, []);
        return static::process($this->compile($template, $tags), $vars, $tags);
    }

    protected function compile(string $template, array $tags) : array
    {
        $root      = ['tag' => '', 'code' => '', 'nodes' => []];
        $leftovers = $this->removeComments($template);
        while (!empty($leftovers)) {
            [$root, $leftovers] = $this->parse($leftovers, $root, $tags);
        }
        if (!empty($this->open_tags)) {
            throw new LogicException("Missing close tag {% end".end($this->open_tags)." %}, reached end of template text.");
        }
        return $root;
    }

    protected function parse(string $leftovers, array $parent, array $tags) : array
    {
        $prev = null;
        if (!empty($parent['nodes'])) {
            $prev = &$parent['nodes'][count($parent['nodes']) - 1];
            if ($prev === null || !is_array($prev) || ($tags[$prev['tag']]['type'] ?? null) === static::FUNCTION_TAG) {
                $prev = null;
            }
        }

        [$content, $tag, $code, $leftovers] = $this->next($leftovers);
        // Remove close tag mark(=null) if exists, then add contents
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
            if (Strings::startsWith($tag, 'end') && !array_key_exists($tag, $tags)) {
                $close_tag = 'end'.array_pop($this->open_tags);
                if ($tag !== $close_tag) {
                    throw new LogicException(
                        $close_tag === 'end'
                        ? "Missing open tag {% ".Strings::ltrim($tag, 'end')." %} , {% {$tag} %} found."
                        : "Missing close tag {% {$close_tag} %}, {% {$tag} %} found."
                    );
                }

                // Set close tag mark(=null)
                $parent['nodes'][] = null;
                return [$parent, $leftovers];
            }

            if (in_array($tag, $tags[end($this->open_tags)]['siblings'][$prev_tag] ?? [])) {
                $parent['nodes'][] = ['tag' => $tag, 'code' => $code, 'nodes' => []];
                return $this->parse($leftovers, $parent, $tags);
            }

            if (empty($tags[$tag])) {
                throw new LogicException("Unsupported (or invalid position) tag {% {$tag} %} found.");
            }

            if (!$prev) {
                $prev = &$parent;
            }
            if ($tags[$tag]['type'] === static::BLOCK_TAG) {
                $this->open_tags[]  = $tag;
            }
            $prev['nodes'][]    = ['tag' => $tag, 'code' => $code, 'nodes' => []];
            [$prev, $leftovers] = $this->parse($leftovers, $prev, $tags);
            return [$parent, $leftovers];
        }

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

    public static function process(array $nodes, array $vars, array $tags) : string
    {
        $contents = '';
        $family   = [];
        $siblings = [];
        $open_tag = null;
        $prev_tag = null;
        foreach ($nodes as $node) {
            if (is_string($node)) {
                if (!empty($family)) {
                    $contents .= call_user_func($tags[$open_tag]['handler'], $family, $vars, $tags);
                }
                $contents .= static::expandVars($node, $vars);
                continue;
            }

            $tag = $node['tag'];
            if ($tags[$tag]['type'] === static::FUNCTION_TAG) {
                if (!empty($family)) {
                    $contents .= call_user_func($tags[$open_tag]['handler'], $family, $vars, $tags);
                }
                $contents .= call_user_func($tags[$tag]['handler'], $node, $vars, $tags);
                continue;
            }

            if (!empty($family)) {
                if (in_array($tag, $siblings[$prev_tag] ?? [])) {
                    $prev_tag = $tag;
                    $family[] = $node;
                    continue;
                }

                $contents .= call_user_func($tags[$open_tag]['handler'], $family, $vars, $tags);
            }

            $open_tag = $tag;
            $prev_tag = $tag;
            $siblings = $tags[$open_tag]['siblings'];
            $family   = [$node];
        }

        return $contents .= call_user_func($tags[$open_tag]['handler'], $family, $vars, $tags);
    }

    /**
     * Expand placeholder in template using given vars.
     *
     * @param string $template
     * @param array|Stream $vars
     * @return string
     */
    public static function expandVars(string $template, $vars) : string
    {
        return preg_replace_callback('/(\s{{-|{{)(?<code>[\s\S]*)(}}|-}}\s)/Uu', function ($matches) use ($vars) {
            return static::eval(trim($matches['code']), $vars);
        }, $template);
    }

    /**
     * Execute PHP partial code.
     *
     * @param string $__code
     * @param array|Stream $__vars
     * @return void
     */
    public static function eval(string $__code, $__vars) : string
    {
        $__vars = Stream::of($__vars);
        foreach ($__vars as $__name => $__value) {
            ${$__name} = $__value;
        }
        return (string) eval($__code);
    }
}
