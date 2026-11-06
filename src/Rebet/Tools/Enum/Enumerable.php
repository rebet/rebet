<?php

declare(strict_types=1);

namespace Rebet\Tools\Enum;

use Override;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Translation\Translator;
use Rebet\Tools\Utility\Arrays;
use Rebet\Tools\Utility\Strings;

/**
 * Enumerable Trait
 *
 * NOTE: This trait is intended to be used by native enums, which cannot have properties,
 *       so this trait must not declare any properties.
 *
 * === HOW TO USE ===
 * # Backed Enum Definition (Recommended)
 * ---------------
 * // with (at)template-implements Enumeration<Gender, int>
 * enum Gender: int implements Enumeration {
 *     // with (at)template-use Enumerable<Gender, int>
 *     use Enumerable;
 *
 *     case Male   = 1;
 *     case Female = 2;
 * }
 *
 * => Gender::Male          // Access Enum Object
 * => Gender::Male->name()  // return 'Male'
 * => Gender::Male->value() // return 1
 * => Gender::Male->label() // return '男性' when configured `{Gender::class}.label => [1 => '男性', 2 => '女性']` in i18n/ja/enum.php and default locale is 'ja'.
 *                          //  (the label is 'Male' when the translation is not found)
 *
 * # Pure Enum Definition
 * ---------------
 * // with (at)template-implements Enumeration<Gender, string>
 * enum Gender implements Enumeration {
 *     // with (at)template-use Enumerable<Gender, string>
 *     use Enumerable;
 *
 *     case Male;
 *     case Female;
 * }
 *
 * => Gender::Male          // Access Enum Object
 * => Gender::Male->name()  // return 'Male'
 * => Gender::Male->value() // return 'Male' (fallback name())
 * => Gender::Male->label() // return '男性' when configured `{Gender::class}.label => ['Male' => '男性', 'Female' => '女性']` in i18n/ja/enum.php and default locale is 'ja'.
 *                          //  (the key of the translation is the name() instead of the value() for pure enum)
 *
 * # Method extension
 * ---------------
 * // with (at)template-implements Enumeration<Gender, int>
 * enum Gender: int implements Enumeration {
 *     // with (at)template-use Enumerable<Gender, int>
 *     use Enumerable;
 *
 *     case Male   = 1;
 *     case Female = 2;
 *
 *     public function isMale()   { return $this === self::Male; }
 *     public function isFemale() { return $this === self::Female; }
 * }
 *
 * # Field(Metadata) extension
 * ---------------
 * // with (at)template-implements Enumeration<Gender, int>
 * enum Gender: int implements Enumeration {
 *     // with (at)template-use Enumerable<Gender, int>
 *     use Enumerable;
 *
 *     #[Meta(color: 'black', icon: 'fa-solid fa-person')]
 *     case Male   = 1;
 *
 *     #[Meta(color: 'red', icon: 'fa-solid fa-person-dress')]
 *     case Female = 2;
 * }
 *
 * => Gender::Male->field('color')           // return 'black'
 * => Gender::Male->color()                  // return 'black' (same as field('color'))
 * => Gender::Male->icon()                   // return 'fa-solid fa-person' (same as field('icon'))
 * => Gender::Male->translate('color', 'ja') // return the translated 'color' or 'orange' (as a string) when the translation is not found
 *
 * # Simple labeling (you don't need to prepare i18n files)
 * ---------------
 * // with (at)template-implements Enumeration<Gender, int>
 * enum Gender: int implements Enumeration {
 *     // with (at)template-use Enumerable<Gender, int>
 *     use Enumerable;
 *
 *     #[Meta(label: '男性')]
 *     case Male   = 1;
 *
 *     #[Meta(label: '女性')]
 *     case Female = 2;
 * }
 *
 * => Gender::Male->label() // return '男性' (the translation is prior to the Meta label)
 * NOTE: 'value' and 'name' can not be used as a metadata name. (see Meta::RESERVED_NAMES)
 *
 * # Database Master Reference (the class that is not native enum)
 * ---------------
 * // with (at)template-implements Enumeration<Prefecture, int>
 * class Prefecture implements Enumeration {
 *     // with (at)template-use Enumerable<Prefecture, int>
 *     use Enumerable;
 *
 *     private static array|null $cache = null;
 *
 *     public function __construct(
 *         public readonly int $value,
 *         public readonly string $name,
 *         public readonly string $label,
 *     ) {
 *     }
 *
 *     public static function cases(): array {
 *         return self::$cache ??= Dao::select('SELECT prefecture_id AS value, code AS name, name AS label FROM prefecture ORDER BY prefecture_id ASC', [], Prefecture::class)->all();
 *     }
 *
 *     protected static function translatable(): bool {
 *         return false;
 *     }
 * }
 *
 * // Customize
 * //  - translatable()      : Override with false if the labels does not need to be translated. (default: true)
 * //  - translationGroup()  : Override if you want to change the translator group. (default: 'enum')
 * //  - nexts()             : Override if you want to use as a simple workflow.
 * //  Note: Native enum can not override the constant of trait and can not have the properties or the magic methods (__get, __toString and so on),
 * //        so that these customization points are the static methods.
 * //  Note: The comparison of equals(), in() and valueOf() is loose, that is compared as the array key by Arrays::tryAsKey().
 * //        (e.g. 1 equals '1' and true, but not '01'. float, null, array and the other object are never equal)
 *
 * @template T of Enumeration
 * @template V of int|string
 *
 * @phpstan-require-implements Enumeration<T, V>
 *
 * @property string $name Pure/Backed enum `name` or Object property of `name`
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait Enumerable // @phpstan-ignore trait.unused
{
    /**
     * Get all cases of this enumeration.
     *
     * The using class must implement this method to define its cases
     * (e.g. by constants, a fixed list or a database master table).
     * Implementations should cache the result, since it may be called repeatedly.
     *
     * @return array<int, T>
     */
    abstract public static function cases(): array;

    /**
     * Get translatable status of this enum.
     * This method always returns true.
     * Please override this method with false in enumeration which does not require translation.
     *
     * @return bool
     */
    protected static function translatable(): bool
    {
        return true;
    }

    /**
     * Get the translation group name.
     * This method always returns 'enum'.
     * Please override this method if you want to change the translator group.
     *
     * Note: Native enum can not override the constant of trait, so this is a method.
     *
     * @return string
     */
    protected static function translationGroup(): string
    {
        return 'enum';
    }

    /**
     * Get the name of this case.
     *
     * This is the same as the name property (that can not be translated).
     *
     * @return string
     */
    #[Override]
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get the value of this case.
     *
     * If this enum is pure enum (that has no value) then the name is returned as the value.
     *
     * @return V
     */
    #[Override]
    public function value(): int|string
    {
        return $this->field('value');
    }

    /**
     * Get the value that is used as an array key. (the value of this case)
     *
     * @return V
     */
    #[Override]
    public function asArrayKey(): int|string
    {
        return $this->value();
    }

    /**
     * Get the translated label.
     *
     * This is the same as translate('label', $locale).
     *
     * @param  string|null $locale (default: depend on configure)
     * @return string
     */
    #[Override]
    public function label(string|null $locale = null): string
    {
        return $this->translate('label', $locale);
    }

    /**
     * Get translated value of given field/meta as a string.
     *
     * Try to get the translated value from the translator, and return the field value (converted to string by Strings::stringify())
     * when the translation is not found.
     * The 'name' and 'value' (Meta::RESERVED_NAMES) are identifiers (not display text), so they are never translated.
     * If this enum is not translatable then the translation is always skipped.
     *
     * @param  string         $field
     * @param  string|null    $locale (default: depend on configure)
     * @return string
     * @throws LogicException when the given field is not defined
     */
    #[Override]
    public function translate(string $field, string|null $locale = null): string
    {
        $value = $this->field($field);
        if (!static::translatable() || in_array($field, Meta::RESERVED_NAMES, true)) {
            return Strings::stringify($value);
        }
        $key        = get_called_class() . ".{$field}.{$this->value()}";
        $translated = Translator::get(static::translationGroup() . ".{$key}", [], null, true, $locale);
        return $translated ?? Strings::stringify($value);
    }

    /**
     * Get the field value or the metadata of this case.
     *
     * The value is resolved in the following order.
     *   1. The property of the name exists then the value of it is returned.
     *   2. The metadata of the name defined by the Meta attribute.
     *   3. The case name if the name is 'value' or 'label'.
     *
     * The value is not translated and the type is kept. Use translate() or label() if you need the translated value.
     *
     * @param  string         $field
     * @return mixed
     * @throws LogicException when the given field is not defined
     */
    #[Override]
    public function field(string $field): mixed
    {
        // The shortcut for the properties that all native enums have (to avoid the reflection, it is about 10 times faster)
        if ($this instanceof \UnitEnum) {
            if ($field === 'name') {
                return $this->name;
            }
            if ($field === 'value') {
                return $this instanceof \BackedEnum ? $this->value : $this->name;
            }
        }

        if (Reflector::has($this, $field)) {
            return Reflector::get($this, $field);
        }
        $meta = $this->metadata();
        if (array_key_exists($field, $meta)) {
            return $meta[$field];
        }
        if ($field === 'value' || $field === 'label') {
            return $this->name;
        }
        throw new LogicException("Undefined field or meta. " . static::class . "::{$this->name} has no field or meta '{$field}'.");
    }

    /**
     * Check this enum equals given value.
     * The value is compared as the array key by Arrays::tryAsKey(). (e.g. 1 equals '1' and true, same as valueOf()), and float, null, array and the other object never equal.
     *
     * @param  mixed $value
     * @return bool
     */
    #[Override]
    public function equals($value): bool
    {
        if ($value instanceof static) {
            return $this === $value;
        }
        if (is_object($value)) {
            return false;
        }
        $expect = Arrays::tryAsKey($value);
        return $expect !== null && $expect === Arrays::asKey($this);
    }

    /**
     * Verify that the enumeration is included in the given array.
     *
     * @param  mixed ...$values
     * @return bool
     */
    #[Override]
    public function in(...$values): bool
    {
        foreach ($values as $value) {
            if ($this->equals($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the metadata defined by the Meta attribute of this case.
     *
     * Non enum class (e.g. database master reference) has no Meta attribute, so it always has no metadata.
     *
     * @return array<string, mixed>
     */
    private function metadata(): array
    {
        static $METADATA_CACHE = [];

        if (!($this instanceof \UnitEnum)) {
            return [];
        }
        $class = static::class;
        if (!isset($METADATA_CACHE[$class][$this->name])) {
            $attribute                           = (new \ReflectionEnumUnitCase($class, $this->name))->getAttributes(Meta::class)[0] ?? null;
            $METADATA_CACHE[$class][$this->name] = $attribute?->newInstance()->data ?? [];
        }
        return $METADATA_CACHE[$class][$this->name];
    }

    /**
     * Provides metadata access via method call. (e.g. `$status->color()`)
     *
     * The arguments are ignored. Use translate() if you need the translated value.
     *
     * Note: Native enum can not have magic method __get, so we can not access the metadata as property.
     *
     * @param  string         $field
     * @param  array<mixed>   $args
     * @return mixed
     * @throws LogicException when the given field is not defined
     */
    public function __call(string $field, array $args): mixed
    {
        return $this->field($field);
    }

    /**
     * Get JSON Serialize objects. (the value of this case)
     *
     * @return V
     */
    #[Override]
    public function jsonSerialize(): int|string
    {
        return $this->value();
    }

    /**
     * Convert type to given type.
     *
     * @param  string $type
     * @return mixed
     */
    #[Override]
    public function convertTo(string $type)
    {
        if ($type === static::class) {
            return $this;
        }
        $value = $this->value();
        return match (true) {
            Reflector::typeOf($value, $type) => $value,
            $type === 'string'               => (string) $value,
            default                          => null,
        };
    }

    /**
     * Get a list of enum.
     *
     * @param  (callable(T $enum): bool)|null $matcher the condition of enum to be included in the list (default: null)
     * @return array<int, T>
     */
    #[Override]
    public static function lists(callable|null $matcher = null): array
    {
        $cases = static::cases();
        return $matcher === null ? $cases : array_values(array_filter($cases, $matcher));
    }

    /**
     * Get map of [$enum->field/meta ⇒ $enum].
     * If there is an enum with the same field/meta value, it wins after the enum::lists().
     * The key is the canonical form of the array key by Arrays::asKey(). (e.g. true is 1)
     *
     * @param  string                         $field   (default: 'value')
     * @param  (callable(T $enum): bool)|null $matcher the condition of enum to be included in the map (default: null)
     * @return array<int|string, T>
     * @throws LogicException                 when the given field is not defined or the field value can not be used as the array key (e.g. array, object)
     */
    #[Override]
    public static function maps(string $field = 'value', callable|null $matcher = null): array
    {
        $maps = [];
        foreach (static::lists($matcher) as $enum) {
            $key = Arrays::tryAsKey($enum->field($field));
            if ($key === null) {
                throw new LogicException("The field '{$field}' of " . static::class . "::{$enum->name()} can not be used as the key of map.");
            }
            $maps[$key] = $enum;
        }
        return $maps;
    }

    /**
     * Get an enum with the value of the given field.
     * If there is an enum with the same filed value, it wins after enum::lists().
     * The value is compared as the array key by Arrays::tryAsKey(). (e.g. '1' and true match 1) and
     * the untranslated field value is searched when the translate is false.
     * The value that can not be used as the array key (e.g. array) matches nothing.
     *
     * @param  string         $field
     * @param  mixed          $value
     * @param  bool           $translate (default: false)
     * @param  string|null    $locale    (default: depend on configure)
     * @return T|null
     * @throws LogicException
     */
    #[Override]
    public static function fieldOf(string $field, $value, bool $translate = false, string|null $locale = null): self|null
    {
        if ($value instanceof static) {
            return $value;
        }
        if (is_object($value)) {
            return null;
        }
        $expect = Arrays::tryAsKey($value);
        if ($expect === null) {
            return null;
        }
        if (!$translate) {
            return static::fieldIndex($field)[$expect] ?? null;
        }

        $found = null;
        foreach (static::lists() as $enum) {
            if (Arrays::tryAsKey($enum->translate($field, $locale)) === $expect) {
                $found = $enum;
            }
        }
        return $found;
    }

    /**
     * Get a list of given field/meta of the given enums.
     *
     * @param  array<int, T>     $enums
     * @param  string            $field
     * @param  bool              $translate (default: false)
     * @param  string|null       $locale    (default: depend on configure)
     * @return array<int, mixed>
     * @throws LogicException    when the given field is not defined
     */
    private static function pluck(array $enums, string $field, bool $translate, string|null $locale): array
    {
        $values = [];
        foreach ($enums as $enum) {
            $values[] = $translate ? $enum->translate($field, $locale) : $enum->field($field);
        }
        return $values;
    }

    /**
     * Get the index of [untranslated field/meta value (as the array key) => enum].
     * The enum that has the value which can not be used as the array key (e.g. array, object) is not included.
     * If there is an enum with the same field/meta value, it wins after enum::lists().
     *
     * The index is cached for each class and field only when the class is native enum,
     * because the cases of native enum are never changed while the process is running.
     * (The cases of other class, e.g. database master reference, may be changed by cases() so that it is not cached.)
     *
     * @param  string           $field
     * @return array<string, T>
     * @throws LogicException   when the given field is not defined
     */
    private static function fieldIndex(string $field): array
    {
        static $INDEX_CACHE = [];

        $class = static::class;
        if (isset($INDEX_CACHE[$class][$field])) {
            return $INDEX_CACHE[$class][$field];
        }

        $index = [];
        foreach (static::lists() as $enum) {
            if (($key = Arrays::tryAsKey($enum->field($field))) !== null) {
                $index[$key] = $enum;
            }
        }
        if (enum_exists($class)) {
            $INDEX_CACHE[$class][$field] = $index;
        }
        return $index;
    }

    /**
     * Get an enum with the target value.
     * If there is an enum with the same filed value, it wins after enum::lists().
     *
     * @param  V      $value
     * @return T|null
     */
    #[Override]
    public static function valueOf($value): self|null
    {
        return static::fieldOf('value', $value);
    }

    /**
     * Get an enum with the target label.
     * The label is compared with the translated label. (Use fieldOf('label', $label) to compare with the untranslated label)
     * If there is an enum with the same filed value, it wins after enum::lists().
     *
     * @param  string      $label
     * @param  string|null $locale (default: depend on configure)
     * @return T|null
     */
    #[Override]
    public static function labelOf(string $label, string|null $locale = null): self|null
    {
        return static::fieldOf('label', $label, true, $locale);
    }

    /**
     * Get an enum with the target name.
     *
     * @param  string $name
     * @return T|null
     */
    #[Override]
    public static function nameOf(string $name): self|null
    {
        return static::fieldOf('name', $name);
    }

    /**
     * Get a list of given field/meta as an array.
     * The field/meta is resolved by field() so that the property and the metadata defined by the Meta attribute are available.
     *
     * @param  string                         $field
     * @param  (callable(T $enum): bool)|null $matcher   (default: null)
     * @param  bool                           $translate (default: false)
     * @param  string|null                    $locale    (default: depend on configure)
     * @return array<int, mixed>
     * @throws LogicException                 when the given field is not defined
     */
    #[Override]
    public static function listOf(string $field, callable|null $matcher = null, bool $translate = false, string|null $locale = null): array
    {
        return static::pluck(static::lists($matcher), $field, $translate, $locale);
    }

    /**
     *  Get a list of value as an array.
     *
     * @param  (callable(T $enum): bool)|null $matcher (default: null)
     * @return array<int, V>
     */
    #[Override]
    public static function values(callable|null $matcher = null): array
    {
        return static::listOf('value', $matcher);
    }

    /**
     *  Get a list of translated label as an array.
     *
     * @param  (callable(T $enum): bool)|null $matcher (default: null)
     * @param  string|null                    $locale  (default: depend on configure)
     * @return array<int, string>
     */
    #[Override]
    public static function labels(callable|null $matcher = null, string|null $locale = null): array
    {
        return static::listOf('label', $matcher, true, $locale);
    }

    /**
     *  Get a list of name as an array.
     *
     * @param  (callable(T $enum): bool)|null $matcher (default: null)
     * @return array<int, string>
     */
    #[Override]
    public static function names(callable|null $matcher = null): array
    {
        return static::listOf('name', $matcher);
    }

    /**
     * Simple Workflow.
     * Get the next enumeration list that can transition from an enumerated value(current) according to the given situation(context).
     * Override with subclass if necessary.
     *
     * @param  T|V|null          $current
     * @param  array<mixed>|null $context (default: null)
     * @return array<int, T>
     */
    #[Override]
    public static function nexts(mixed $current, array|null $context = null): array
    {
        return static::lists();
    }

    /**
     * Simple Workflow.
     * Get the next enumeration list of given fields as an array.
     * The field/meta is resolved by field() so that the property and the metadata defined by the Meta attribute are available.
     *
     * @param  string            $field
     * @param  T|V|null          $current
     * @param  array<mixed>|null $context   (default: null)
     * @param  bool              $translate (default: false)
     * @param  string|null       $locale    (default: depend on configure)
     * @return array<int, mixed>
     * @throws LogicException    when the given field is not defined
     */
    #[Override]
    public static function nextOf(string $field, mixed $current, array|null $context = null, bool $translate = false, string|null $locale = null): array
    {
        return static::pluck(static::nexts($current, $context), $field, $translate, $locale);
    }

    /**
     * Simple Workflow.
     * Get the next enumeration values as an array.
     *
     * @param  T|V|null          $current
     * @param  array<mixed>|null $context (default: null)
     * @return array<int, V>
     */
    #[Override]
    public static function nextValues(mixed $current, array|null $context = null): array
    {
        return static::nextOf('value', $current, $context);
    }

    /**
     * Simple Workflow.
     * Get the next enumeration translated labels as an array.
     *
     * @param  T|V|null           $current
     * @param  array<mixed>|null  $context (default: null)
     * @param  string|null        $locale  (default: depend on configure)
     * @return array<int, string>
     */
    #[Override]
    public static function nextLabels(mixed $current, array|null $context = null, string|null $locale = null): array
    {
        return static::nextOf('label', $current, $context, true, $locale);
    }

    /**
     * Simple Workflow.
     * Get the next enumeration names as an array.
     *
     * @param  T|V|null           $current
     * @param  array<mixed>|null  $context (default: null)
     * @return array<int, string>
     */
    #[Override]
    public static function nextNames(mixed $current, array|null $context = null): array
    {
        return static::nextOf('name', $current, $context);
    }
}
