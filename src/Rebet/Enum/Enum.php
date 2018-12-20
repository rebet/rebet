<?php
namespace Rebet\Enum;

use Rebet\Common\Convertible;
use Rebet\Common\Reflector;
use Rebet\Config\Config;
use Rebet\Config\Configurable;
use Rebet\Translation\FileLoader;
use Rebet\Translation\Translator;

/**
 * Enum Class
 *
 * === HOW TO USE ===
 * // Basic Usage (when fallback locale is 'en')
 * // You should define the label by fallback locale language.
 * class Gender extends Enum {
 *     const MALE   = [1, 'Male'];
 *     const FEMALE = [2, 'Female'];
 * }
 *
 * => Gender::MALE() // Access Enum Object
 *
 * // Without Const
 * class Gender extends Enum {
 *     protected static function generate() {
 *         return [
 *             new static('M', 'Male'),
 *             new static('F', 'Female'),
 *         ];
 *     }
 * }
 *
 * // Method extension
 * class Gender extends Enum {
 *     const MALE   = [1, 'Male'];
 *     const FEMALE = [2, 'Female'];
 *
 *     public function isMale()   { return $this->value === 1; }
 *     public function isFemale() { return $this->value === 2; }
 * }
 *
 * // Field extension
 * class AcceptStatus extends Enum {
 *     const WAITING  = [1, '待機中', 'orange', 'far fa-clock'];
 *     const ACCEPTED = [2, '受理'  , 'green' , 'fas fa-check-circle'];
 *     const REJECTED = [3, '却下'  , 'red'   , 'fas fa-times-circle'];
 *
 *     public $color;
 *     public $icon;
 *
 *     protected function __construct($value, $label, $color, $icon) {
 *         parent::__construct($value, $label);
 *         $this->color = $color;
 *         $this->icon  = $icon;
 *     }
 * }
 *
 * // Anonymous  class extension
 * abstract class JobOfferCsvFormat extends Enum {
 *     public abstract function convert(array $row) : UserForm ;
 *
 *     protected static function generate() {
 *         return [
 *              new class(1, 'Job Site A') extends JobOfferCsvFormat {
 *                  public function convert(array $row) : UserForm {
 *                      $form = new UserForm();
 *                      (snip)
 *                      $form->name = "{$row[0]} {$row[1]}"; // combine first name and last name column.
 *                      (snip)
 *                      return $form;
 *                  }
 *              },
 *              new class(2, 'Job Site B') extends JobOfferCsvFormat {
 *                  public function convert(array $row) : UserForm {
 *                      $form = new UserForm();
 *                      (snip)
 *                      $form->name = $row[5]; // just use full name column.
 *                      (snip)
 *                      return $form;
 *                  }
 *              }
 *         ];
 *     }
 * }
 *
 * // Database master reference
 * class Prefecture extends Enum {
 *     public function __construct() {
 *         parent::__construct(null, null);
 *     }
 *
 *     protected static function generate() {
 *         return Dao::select('SELECT prefecture_id AS value, name AS label FROM prefecture ORDER BY prefecture_id ASC', [], Prefecture::class);
 *     }
 * }
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class Enum implements \JsonSerializable, Convertible
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'resources' => [
                'i18n' => [],
            ],
        ];
    }

    /**
     * Enum Data Cache
     *
     * self::$enum_data_cache = [
     *     EnumClassName => [
     *         ConstName => EnumObject,
     *     ],
     * ]
     */
    private static $enum_data_cache = [];
    
    /**
     * Enum List Data Cache
     *
     * self::$enum_list_cache = [
     *     EnumClassName => [EnumObject, ... ],
     * ]
     */
    private static $enum_list_cache = [];
    
    /**
     * Enum Map Data Cache
     *
     * self::$enum_map_cache = [
     *     EnumClassName@FieldName => [
     *         FieldValue => EnumObject,
     *     ],
     * ]
     */
    private static $enum_map_cache  = [];

    /**
     * Translator for label
     *
     * @var Translator
     */
    protected static $translator = null;

    /**
     * Value of enum.
     * @var mixed
     */
    public $value = null;
    
    /**
     * Label of enum.
     * This label is not translated.
     * If you want to get translated label, you must be use translate() method.
     *
     * @var string
     */
    public $label = null;
    
    /**
     * The constant name of enum.
     *
     * @var string|null
     */
    public $name = null;

    /**
     * Clear the cache of given class or all enums.
     *
     * @param string|null $class
     * @return void
     */
    public static function clear(?string $class = null) : void
    {
        if ($class) {
            unset(
                static::$enum_data_cache[$class],
                static::$enum_list_cache[$class],
                static::$enum_map_cache[$class]
            );
        } else {
            static::$enum_data_cache = [];
            static::$enum_list_cache = [];
            static::$enum_map_cache  = [];
        }
        static::$translator = null;
    }

    /**
     * Get the translator for enums.
     *
     * @return Translator
     */
    protected static function translator() : Translator
    {
        if (static::$translator) {
            return static::$translator;
        }
        static::$translator = new Translator(new FileLoader(Enum::config('resources.i18n', false, [])));

        return static::$translator;
    }

    /**
     * Create an enum object
     *
     * @param mixed $value
     * @param string $label
     * @throws \LogicException
     */
    protected function __construct($value, string $label)
    {
        if (!\is_scalar($value)) {
            throw new \LogicException("Invalid value type. Value should be scalar.");
        }
        $this->value = $value;
        $this->label = $label;
    }

    /**
     * Get translatable status of this enum.
     * This method always returns true.
     * Please override this method with false in enumeration which does not require translation.
     *
     * @return boolean
     */
    protected function translatable() : bool
    {
        return true;
    }

    /**
     * Get translated value of given field.
     * If this enum is not translatable then return value of given field as it is.
     *
     * @param string $field (default: label)
     * @param string|null $locale (default: depend on configure)
     * @return string
     */
    public function translate(string $field = 'label', ?string $locale = null) : string
    {
        if (!$this->translatable()) {
            return $this->$field;
        }
        $class      = get_called_class();
        $key        = "{$class}.{$field}.{$this->value}";
        $translated = static::translator()->get("enum.{$key}", [], null, $locale);
        return $translated === null ? $this->$field : $translated ;
    }

    /**
     * Check this enum equals given value.
     *
     * @param mixed $value
     * @return bool
     */
    public function equals($value) : bool
    {
        return $value instanceof static ? $this === $value : $this->value == $value ;
    }
    
    /**
     * Verify that the enumeration is included in the given array.
     *
     * @param mixed ...$values
     * @return boolean
     */
    public function in(...$values) : bool
    {
        foreach ($values as $value) {
            if ($this->equals($value)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get the translated label.
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->translate();
    }
    
    /**
     * Get JSON Serialize objects.
     */
    public function jsonSerialize()
    {
        return $this->value;
    }

    /**
     * Convert type to given type.
     *
     * @param string $type
     * @return void
     */
    public function convertTo(string $type)
    {
        if ($type === static::class) {
            return $this;
        }
        if (Reflector::typeOf($this->value, $type)) {
            return $this->value;
        }
        switch ($type) {
            case 'string':
                return (string)$this->value;
        }
        return null;
    }

    /**
     * Creates an enum object from const definition of enum type.
     *
     * @param \ReflectionClass $rc
     * @param string $name
     * @return self|null
     */
    private static function constToEnum(\ReflectionClass $rc, string $name) : ?self
    {
        $class = $rc->getName();
        if (isset(self::$enum_data_cache[$class][$name])) {
            return self::$enum_data_cache[$class][$name];
        }
        if (!defined("static::{$name}")) {
            throw new \LogicException("Invalid enum const. {$class}::{$name} is not defined.");
        }

        $args       = $rc->getConstant($name);
        $enum       = new static(...$args);
        $enum->name = $name;

        self::$enum_data_cache[$class][$name] = $enum;
        return $enum;
    }

    /**
     * Provides enumerated object access via static method call.
     *
     * @param string $name
     * @param array $args
     * @return void
     */
    public static function __callStatic(string $name, array $args)
    {
        return self::constToEnum(new \ReflectionClass(get_called_class()), $name);
    }

    /**
     * Generate a list of enums.
     *
     * @return array
     */
    protected static function generate() : array
    {
        $rc   = new \ReflectionClass(get_called_class());
        $list = [];
        foreach ($rc->getConstants() as $key => $args) {
            $list[] = self::constToEnum($rc, $key);
        }
        return $list;
    }
    
    /**
     * Get a list of enum.
     * # Cache and reuse the enum list generated by enum class name unit.
     *
     * @return array
     */
    public static function lists() : array
    {
        $class = get_called_class();
        if (isset(self::$enum_list_cache[$class])) {
            return self::$enum_list_cache[$class];
        }
        self::$enum_list_cache[$class] = static::generate();
        return self::$enum_list_cache[$class];
    }
    
    /**
     * Get map of [$enum->$field ⇒ $enum].
     * If there is an enum with the same filed value, it wins after enum::lists().
     *
     * @param string $field (default: 'value')
     * @param boolean $translate (default: false)
     * @param string|null $locale (default: depend on configure)
     * @return array
     * @throws \LogicException
     */
    public static function maps(string $field = 'value', bool $translate = false, ?string $locale = null) : array
    {
        $class = get_called_class();
        if (!\property_exists($class, $field)) {
            throw new \LogicException("Invalid property access. Property {$class}->{$field} is not exists.");
        }

        $locale = $locale ?? static::translator()->getLocale();
        $key    = $translate ? "{$class}@{$field}:{$locale}" : "{$class}@{$field}";
        if (isset(self::$enum_map_cache[$key])) {
            return self::$enum_map_cache[$key];
        }
        
        $maps = [];
        foreach (self::lists() as $enum) {
            $maps[$translate ? $enum->translate($field, $locale) : $enum->$field] = $enum;
        }
        self::$enum_map_cache[$key] = $maps;
        
        return $maps;
    }
    
    /**
     * Get an enum with the value of the given field.
     * If there is an enum with the same filed value, it wins after enum::lists().
     *
     * @param string $field
     * @param mixed $value
     * @param boolean $translate (default: false)
     * @param string|null $locale (default: depend on configure)
     * @return self|null
     * @throws \LogicException
     */
    public static function fieldOf(string $field, $value, bool $translate = false, ?string $locale = null) : ?self
    {
        if ($value instanceof static) {
            return $value;
        }
        if (is_object($value)) {
            return null;
        }
        $maps = self::maps($field, $translate, $locale);
        return isset($maps[$value]) ? $maps[$value] : null ;
    }
    
    /**
     * Get an enum with the target value.
     * If there is an enum with the same filed value, it wins after enum::lists().
     *
     * @param mixed $value
     * @return self|null
     */
    public static function valueOf($value) : ?self
    {
        return self::fieldOf('value', $value);
    }
    
    /**
     * Get an enum with the target label.
     * If there is an enum with the same filed value, it wins after enum::lists().
     *
     * @param string $label
     * @param boolean $translate (default: false)
     * @param string|null $locale (default: depend on configure)
     * @return self|null
     */
    public static function labelOf(string $label, bool $translate = false, ?string $locale = null) : ?self
    {
        return self::fieldOf('label', $label, $translate, $locale);
    }
    
    /**
     * Get an enum with the target name.
     * If there is an enum with the same filed value, it wins after enum::lists().
     *
     * @param string $name
     * @return self|null
     */
    public static function nameOf(string $name) : ?self
    {
        return self::fieldOf('name', $name);
    }

    /**
     * Get a list of given field as an array.
     *
     * @param string $name
     * @param \Closure $matcher (default: null)
     * @param boolean $translate (default: false)
     * @param string|null $locale (default: depend on configure)
     * @return array
     */
    public static function listOf(string $name, \Closure $matcher = null, bool $translate = false, ?string $locale = null) : array
    {
        $class = get_called_class();
        if (!\property_exists($class, $name)) {
            throw new \LogicException("Invalid property access. Property {$class}->{$name} is not exists.");
        }

        $values = [];
        foreach (self::lists() as $enum) {
            if ($matcher == null || $matcher($enum)) {
                $values[] = $translate ? $enum->translate($name, $locale) : $enum->$name ;
            }
        }
        return $values;
    }
    
    /**
     *  Get a list of value as an array.
     *
     * @param \Closure $matcher (default: null)
     * @param boolean $translate (default: false)
     * @param string|null $locale (default: depend on configure)
     * @return array
     */
    public static function values(\Closure $matcher = null, bool $translate = false, ?string $locale = null) : array
    {
        return self::listOf('value', $matcher, $translate, $locale);
    }
    
    /**
     *  Get a list of label as an array.
     *
     * @param \Closure $matcher (default: null)
     * @param boolean $translate (default: false)
     * @param string|null $locale (default: depend on configure)
     * @return array
     */
    public static function labels(\Closure $matcher = null, bool $translate = false, ?string $locale = null) : array
    {
        return self::listOf('label', $matcher, $translate, $locale);
    }
    
    /**
     *  Get a list of name as an array.
     *
     * @param \Closure $matcher (default: null)
     * @return array
     */
    public static function names(\Closure $matcher = null) : array
    {
        return self::listOf('name', $matcher);
    }

    /**
     * Simple Workflow.
     * Get the next enumeration list that can transition from an enumerated value(current) according to the given situation(context).
     * Override with subclass if necessary.
     *
     * @param type $current
     * @param array|null $context (default: null)
     * @return array
     */
    public static function nexts($current, ?array $context = null) : array
    {
        return self::lists();
    }
    
    /**
     * Simple Workflow.
     * Get the next enumeration list of given fields as an array.
     *
     * @param string $name
     * @param mixed $current
     * @param array|null $context (default: null)
     * @param boolean $translate (default: false)
     * @param string|null $locale (default: depend on configure)
     * @return array
     */
    public static function nextOf(string $name, $current, ?array $context = null, bool $translate = false, ?string $locale = null) : array
    {
        $class = get_called_class();
        if (!\property_exists($class, $name)) {
            throw new \LogicException("Invalid property access. Property {$class}->{$name} is not exists.");
        }

        $values = [];
        foreach (static::nexts($current, $context) as $enum) {
            $values[] = $translate ? $enum->translate($name, $locale) : $enum->$name ;
        }
        return $values;
    }
    
    /**
     * Simple Workflow.
     * Get the next enumeration values as an array.
     *
     * @param mixed $current
     * @param array|null $context (default: null)
     * @return array
     */
    public static function nextValues($current, ?array $context = null) : array
    {
        return self::nextOf('value', $current, $context);
    }
    
    /**
     * Simple Workflow.
     * Get the next enumeration labels as an array.
     *
     * @param mixed $current
     * @param array|null $context (default: null)
     * @return array
     */
    public static function nextLabels($current, ?array $context = null) : array
    {
        return self::nextOf('label', $current, $context);
    }
}
