<?php
namespace Rebet\Validation;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Common\Reflector;
use Rebet\Validation\Annotation\Nest;

/**
 * Validatable Trait
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait Validatable
{
    /**
     * Parent Validatable object
     * If this is nested Validatable object then the parent is stored.
     *
     * @var object using Validatable trait
     */
    protected $_parent_;
    
    /**
     * It copies the value from Map or Dto object to instance variable.
     *
     * @param array|object $src
     * @param array $files Upload file info
     * @param array $option
     * @return self
     */
    public function popurate($src, $files = null, $option = []) : self
    {
        return $this->recursivePopurate($src, $files, $option, '');
    }

    /**
     * It copies the value from Map or Dto object to instance variable for recursive call.
     *
     * @param array|object $src
     * @param array $files Upload file info
     * @param array $option
     * @param string $prefix
     * @return self
     */
    protected function recursivePopurate($src, $files = null, $option = [], string $prefix) : self
    {
        if (empty($src) && empty($files)) {
            return $this;
        }
        
        $class = AnnotatedClass::of($this);
        foreach ($this as $field => $origin) {
            $property = $class->property($field);

            // Analize Nested Validatable Object
            $nest = $property->annotation(Nest::class, false);
            if ($nest) {
                if ($this->isIgnoreNest($option, "{$prefix}{$field}")) {
                    continue;
                }
                $defaults = $class->reflector()->getDefaultProperties();
                $default  = $defaults[$field] ?? null;
                if (is_array($default)) {
                    $this->$field = [];
                    $items = Reflector::get($src, $field);
                    if (empty($items)) {
                        continue;
                    }
                    foreach ($items as $item) {
                        $this->$field[] = $this->createNest($nest->value, $this, $item, $option, "{$prefix}{$field}.");
                    }
                } else {
                    $this->$field = $this->createNest($nest->value, $this, Reflector::get($src, $field), $option, "{$prefix}{$field}.");
                }
                continue;
            }
            
            // @todo Upload File handling

            $this->$field = $this->applyOption($option, $prefix, $field, Reflector::has($src, $field), $src, Reflector::get($src, $field), $this, $origin);
        }

        return $this;
    }

    /**
     * Create nested validatable object
     *
     * @param string $class Nested Validatable Class Name
     * @param object $parent
     * @param array|object $src
     * @param array $option
     */
    private function createNest($class, $parent, $src, array $option, string $prefix)
    {
        $nested = Reflector::instantiate($class);
        Reflector::set($nested, '_parent_', $parent, true);
        Reflector::invoke($nested, 'recursivePopurate', [$src, null, $option, $prefix], true);
        return $nested;
    }
    
    /**
     * Apply Input Option
     *
     * @param array $option
     * @param string $field
     * @param boolean $defined
     * @param array|object $src
     * @param mixed $value
     * @param object $dest
     * @param mixed $origin
     * @return void
     */
    protected function applyOption(array $option, string $prefix, string $field, bool $defined, $src, $value, $dest, $origin)
    {
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
            $includes = Reflector::get($includes, rtrim($prefix, '.'));
            if (!in_array($field, $includes)) {
                return $origin;
            }
        }

        $excludes = $option['excludes'] ?? null;
        if ($excludes) {
            $excludes = Reflector::get($excludes, rtrim($prefix, '.'));
            if (in_array($field, $excludes)) {
                return $origin;
            }
        }

        return $defined ? $value : $origin ;
    }

    /**
     * t checks that an option include ignore nest settings.
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
    
    /**
     * It copies value to the given dest object.
     * # Nested validatable is not processed
     *
     * @param object $dest
     * @param array $option
     */
    public function inject(&$dest, array $option = [])
    {
        $class = AnnotatedClass::of($this);
        foreach ($dest as $field => $origin) {
            $property = $class->property($field);
            if ($property) {
                $nested = $property->annotation(Nest::class, false);
                if ($nested) {
                    continue;
                }
            }
            $dest->$field = $this->applyOption($option, '', $field, Reflector::has($this, $field, true), $this, Reflector::get($this, $field, null, true), $dest, $origin);
        }
        
        return $dest;
    }
    
    /**
     * It creates the given dest class object and copies own value.
     * # Nested validatable is not processed
     *
     * @param string $class
     * @param array $option
     */
    public function describe(string $class, array $option = [])
    {
        $entity = new $class();
        return $this->inject($entity, $option);
    }
}
