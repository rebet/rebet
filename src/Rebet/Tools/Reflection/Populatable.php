<?php
namespace Rebet\Tools\Reflection;

use Rebet\Tools\Arrays;

/**
 * Populatable Trait
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait Populatable
{
    /**
     * It copies the value from Map or Object to instance variable.
     * This method have some options like below,
     *
     * [embeds] : Create nested embed object and then copies property.
     * --------------------------------------
     * 'embeds' => [
     *     ParentClass::class => [
     *         'child' => ChildClass::class,
     *     ],
     *     ChildClass::class => [
     *         'grandchild' => GrandchildClass::class,
     *     ],
     * ]
     *
     * This embeds option will be create dynamic property 'child' to $parent object as ChildClass using source data of $src['child'],
     * and create dynamic property 'grandchild' to $parent->child object as GrandchildClass using source data of $src['child']['grandchild'].
     * If the given src data will be array then embed data also be array.
     * NOTE: 'embeds' class name of key must be used Populatable Trait.
     *
     *
     * [aliases] : Copies property value from different name property and key.
     * --------------------------------------
     * 'aliases' => [
     *     'dest_name' => 'altanate_src_name',
     *     'child' => [
     *         'name' => 'short_name'
     *     ],
     * ],
     *
     *
     * [includes] : Copies property value only given includes properties.
     * --------------------------------------
     * 'includes' => [
     *     'name',
     *     'child' => [
     *         'name'
     *     ],
     * ],
     *
     *
     * [excludes] : Copies other than given property value.
     * --------------------------------------
     * 'excludes' => [
     *     'name',
     *     'child' => [
     *         'name'
     *     ],
     * ],
     *
     * @param array|object $src
     * @param array $options that availables are 'embeds', 'aliases', 'includes' and 'excludes' (default: [])
     * @return self
     */
    public function populate($src, $options = []) : self
    {
        return $this->_populate($src, $options, '');
    }

    /**
     * It copies the value from Map or Object to instance variable for recursive call.
     *
     * @param array|object $src
     * @param array $options that availables are 'embeds', 'aliases', 'includes' and 'excludes'
     * @param string $prefix
     * @return self
     */
    protected function _populate($src, $options = [], string $prefix) : self
    {
        if (empty($src)) {
            return $this;
        }

        foreach ($this as $field => $origin) {
            $this->$field = $this->applyPopulateOption($options, $prefix, $field, $src, $origin);
        }

        foreach ($options['embeds'][get_class($this)] ?? [] as $field => $class) {
            $this->$field = null;
            if ($this->isIgnoreEmbed($options, "{$prefix}{$field}")) {
                continue;
            }

            $items = Reflector::get($src, $field);
            if (empty($items)) {
                continue;
            }

            if (is_array($items) && Arrays::isSequential($items)) {
                $this->$field = [];
                foreach ($items as $item) {
                    $this->$field[] = $this->embed($class, $item, $options, "{$prefix}{$field}.");
                }
            } else {
                $this->$field = $this->embed($class, $items, $options, "{$prefix}{$field}.");
            }
        }

        return $this;
    }

    /**
     * Create embedded populatable object of given class
     *
     * @param string $class name to populate
     * @param array|object $src
     * @param array $options
     * @param string $prefix
     */
    private function embed($class, $src, array $options, string $prefix)
    {
        $embedded = Reflector::instantiate($class);
        Reflector::invoke($embedded, '_populate', [$src, $options, $prefix], true);
        return $embedded;
    }

    /**
     * Apply Input Option
     *
     * @param array $options
     * @param string $prefix
     * @param string $field
     * @param array|object $src
     * @param mixed $origin
     * @return void
     */
    protected function applyPopulateOption(array $options, string $prefix, string $field, $src, $origin)
    {
        $defined = Reflector::has($src, $field);
        $value   = Reflector::get($src, $field);

        if (empty($options)) {
            return $defined ? $value : $origin ;
        }

        $alias = Reflector::get($options, "aliases.{$prefix}{$field}");
        if ($alias) {
            $value = $alias ? Reflector::get($src, $alias) : $value ;
            $field = $alias;
        }

        $includes = $options['includes'] ?? null;
        if ($includes) {
            $includes = Reflector::get($includes, rtrim($prefix, '.'), []);
            if (!in_array($field, $includes)) {
                return $origin;
            }
        } else {
            $excludes = $options['excludes'] ?? null;
            if ($excludes) {
                $excludes = Reflector::get($excludes, rtrim($prefix, '.'), []);
                if (in_array($field, $excludes)) {
                    return $origin;
                }
            }
        }

        return $defined ? $value : $origin ;
    }

    /**
     * It checks that an options include ignore embed settings.
     *
     * @param array $options
     * @param string $field
     * @return bool
     */
    protected function isIgnoreEmbed(array $options, string $field) : bool
    {
        if (empty($options)) {
            return false;
        }

        if (isset($options['includes']) && !Reflector::has($options, rtrim("includes.{$field}", '.'))) {
            return true;
        }

        if (Reflector::has($options, rtrim("exclude.{$field}", '.'))) {
            return true;
        }

        return false;
    }
}
