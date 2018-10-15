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
	public $parent_validatable;
	
	/**
	 * It copies the value from Map or Dto object to instance variable.
	 *  
	 * @param array|object $src
	 * @param array $files Upload file info
	 * @param array $option
	 * @return self
	 */
	public function popurate($src, $files = null, $option = []) : self {
		if(empty($src) && empty($files)) { return $this; }
		
		$class = AnnotatedClass::of($this);
		foreach ($this AS $field => $origin) {
			$property = $class->property($field);

			// Analize Nested Validatable object
			$nest = $property->annotation(Nest::class, false);
			if($nest) {
				$defaults = $class->reflector()->getDefaultProperties();
				$default  = $defaults[$field] ?? null;
				if(is_array($default)) {
					$this->$field = [];
					$items = Reflector::get($src, $field);
					if(empty($items)) { continue; }
					foreach ($items AS $item) {
						$this->$field[] = $this->generateValidatable($nest->value, $this, $item, $option);
					}
				} else {
					$this->$field = $this->generateValidatable($nest->value, $this, Reflector::get($src, $field), $option);
				}
				continue;
			}
			
			// @todo Upload File handling

			$this->$field = $this->applyOption($option, $field, Reflector::has($src, $field), $src, Reflector::get($src, $field), $this, $origin);
		}

		return $this;
	}

	/**
	 * Generate nested validatable object
	 * 
	 * @param string $class Nested Validatable Class name
	 * @param object $parent
	 * @param array|object $src
	 * @param array $option
	 */
	private function generateValidatable($class, $parent, $src, array $option) {
		$nested = Reflector::instantiate($class);
		$nested->parent_validatable = $parent;
		$nested->popurate($src, null, $option);
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
	protected function applyOption(array $option, string $field, bool $defined, $src, $value, $dest, $origin) 
	{
		if(empty($option)) {
			return $defined ? $value : $origin ;
		}

		$alias = $option['aliases'][$field] ?? null;
		if($alias) {
			$value = $alias ? Reflector::get($src, $alias) : $value ;
		}

		$includes = $option['includes'] ?? [];
		if($includes) {
			$include = $includes[$field] ?? null;
			if(!$include) {
				return $origin;
			}
		}

		$exclude = $option['excludes'][$field] ?? [];
		if($exclude) {
			return $origin;
		}

		return $defined ? $value : $origin ;
	}
	
	/**
	 * It copies value to the given dest object.
	 * # Nested validatable is not processed
	 *
	 * @param object $dest
	 * @param array $option
	 */
	public function inject(&$dest, array $option = []) {
		
		$class = AnnotatedClass::of($this);
		foreach ($dest AS $field => $origin) {
			$property = $class->property($field);
			if($property) {
				$nested      = $property->annotation(Validatable::class, false);
				$nested_list = $property->annotation(ValidatableList::class, false);
				if($nested || $nested_list) {
					continue;
				}
			}
			$dest->$field = $this->applyOption($option, $field, Reflector::has($this, $field), $this, Reflector::get($this, $field), $dest, $origin);
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
	public function describe(string $class, array $option = []) {
		$entity = new $class();
		return $this->inject($entity, $option);
	}
	
}

