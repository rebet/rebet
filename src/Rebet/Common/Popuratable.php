<?php
namespace Rebet\Common;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Common\Annotation\Nest;

/**
 * Popuratable Trait
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait Popuratable
{
    /**
     * It copies the value from Map or Dto object to instance variable.
     *
     * @param array|object $src
     * @param array $files Upload file info
     * @param array $option
     * @return self
     */
    public function popurate($src, $option = []) : self
    {
        return $this->_popurate($src, $option, '');
    }

    /**
     * It copies the value from Map or Dto object to instance variable for recursive call.
     *
     * @param array|object $src
     * @param array $option
     * @param string $prefix
     * @return self
     */
    protected function _popurate($src, $option = [], string $prefix) : self
    {
        if (empty($src)) {
            return $this;
        }

        $class = AnnotatedClass::of($this);
        foreach ($this as $field => $origin) {
            $property = $class->property($field);

            // Analize Nested Popuratable Object
            $nest = $property->annotation(Nest::class, false);
            if ($nest) {
                if ($this->isIgnoreNest($option, "{$prefix}{$field}")) {
                    continue;
                }
                $defaults = $class->reflector()->getDefaultProperties();
                $default  = $defaults[$field] ?? null;
                if (is_array($default)) {
                    $this->$field = [];
                    $items        = Reflector::get($src, $field);
                    if (empty($items)) {
                        continue;
                    }
                    foreach ($items as $item) {
                        $this->$field[] = $this->nest($nest->value, $item, $option, "{$prefix}{$field}.");
                    }
                } else {
                    $this->$field = $this->nest($nest->value, Reflector::get($src, $field), $option, "{$prefix}{$field}.");
                }
                continue;
            }

            $this->$field = $this->applyPopurateOption($option, $prefix, $field, $src, $origin);
        }

        return $this;
    }

    /**
     * Create nested validatable object
     *
     * @param string $class nested validatable class name
     * @param array|object $src
     * @param array $option
     * @param string $prefix
     */
    private function nest($class, $src, array $option, string $prefix)
    {
        $nested = Reflector::instantiate($class);
        Reflector::invoke($nested, '_popurate', [$src, $option, $prefix], true);
        return $nested;
    }

    /**
     * Apply Input Option
     *
     * @param array $option
     * @param string $prefix
     * @param string $field
     * @param array|object $src
     * @param mixed $origin
     * @return void
     */
    protected function applyPopurateOption(array $option, string $prefix, string $field, $src, $origin)
    {
        $defined = Reflector::has($src, $field);
        $value   = Reflector::get($src, $field);

        if (empty($option)) {
            return $defined ? $value : $origin ;
        }

        $alias = Reflector::get($option, "aliases.{$prefix}{$field}");
        if ($alias) {
            $value = $alias ? Reflector::get($src, $alias) : $value ;
            $field = $alias;
        }

        $includes = $option['includes'] ?? null;
        if ($includes) {
            $includes = Reflector::get($includes, rtrim($prefix, '.'), []);
            if (!in_array($field, $includes)) {
                return $origin;
            }
        } else {
            $excludes = $option['excludes'] ?? null;
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
     * It checks that an option include ignore nest settings.
     *
     * @param array $option
     * @param string $field
     * @return bool
     */
    protected function isIgnoreNest(array $option, string $field) : bool
    {
        if (empty($option)) {
            return false;
        }

        if (isset($option['includes']) && !Reflector::has($option, rtrim("includes.{$field}", '.'))) {
            return true;
        }

        if (Reflector::has($option, rtrim("exclude.{$field}", '.'))) {
            return true;
        }

        return false;
    }
}
