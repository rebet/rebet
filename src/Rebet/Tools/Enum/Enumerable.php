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
 * => Gender::Male->label() // return 'Male' (the label is the name when the label is not defined by Meta)
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
 * => Gender::Male->original('color') // return 'black'
 * => Gender::Male->color()           // return 'black' (same as original('color'))
 * => Gender::Male->icon()            // return 'fa-solid fa-person' (same as original('icon'))
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
 * => Gender::Male->label() // return '男性'
 * NOTE: 'value' and 'name' can not be used as a metadata name. (see Meta::RESERVED_NAMES)
 *
 * # Translation (Translatable attribute)
 * ---------------
 * The translation is decided only by the Translatable attribute of the class. (all of the translation entrances follow it)
 * The class without the attribute is NOT translated at all (including the label()), so please give the attribute
 * to the class that needs the translation. The attribute defines the fields to be translated and the translation group.
 *
 * - The fields given to the attribute are translated by label(), translate(), labels(), labelOf(), fieldOf(), listOf(), nextOf()
 *   and the method call of the field. (the locale can be specified except for the method call)
 * - The fields not given to the attribute are returned as they are. (Use original() if you need the value as it is.)
 * - The translation is looked up by '{group}.{class}.{field}.{value}' (the name instead of the value for pure enum),
 *   and the field value (the Meta or the name) is used when the translation is not found.
 * - 'value' and 'name' can not be given to the attribute, because they are identifiers. (an exception is thrown)
 * - The attribute of the parent class is inherited, but the attribute of the child class takes precedence.
 *
 * // with (at)template-implements Enumeration<Gender, int>
 * #[Translatable(['label'])]                      // translation group is 'enum' (default)
 * enum Gender: int implements Enumeration {
 *     // with (at)template-use Enumerable<Gender, int>
 *     use Enumerable;
 *
 *     case Male   = 1;
 *     case Female = 2;
 * }
 *
 * => Gender::Male->label()           // return '男性' when configured `{Gender::class}.label => [1 => '男性', 2 => '女性']` in i18n/ja/enum.php and default locale is 'ja'.
 *                                    //  (the label is 'Male' when the translation is not found)
 * => Gender::Male->label('en')       // return the label of the given locale
 * => Gender::Male->original('label') // return 'Male' (not translated)
 *
 * // Multiple fields and the translation group
 * #[Translatable(['label', 'label_short'], group: 'priority')]
 * enum Priority: int implements Enumeration {
 *     // with (at)template-use Enumerable<Priority, int>
 *     use Enumerable;
 *
 *     #[Meta(label: 'High', label_short: 'Hi', label_min: 'H')]
 *     case High = 1;
 *     ...
 * }
 *
 * => Priority::High->label()                // translated by i18n/{locale}/priority.php ('高い' in 'ja')
 * => Priority::High->label_short()          // translated ('高' in 'ja')
 * => Priority::High->label_short('en')      // the locale can be specified ('Hi'), and it is ignored for the field that is not given to the attribute
 * => Priority::High->label_min()            // NOT translated because it is not given to the attribute ('H')
 * => Priority::High->translate('label_min') // NOT translated (the attribute decides all translation entrances)
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
 * }
 * // NOTE: The label is not translated because this class has no Translatable attribute.
 *
 * // Customize
 * //  - nexts()  : Override if you want to use as a simple workflow.
 * //  Note: Native enum can not override the constant of trait and can not have the properties or the magic methods (__get, __toString and so on).
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
trait Enumerable
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
     * Get the translation setting (the Translatable attribute) of this class.
     *
     * The attribute of the nearest class is used, searching the parent classes in order. (the native enum has no parent)
     * If the class and its parents have no attribute then the translation setting is empty, so nothing is translated.
     * The setting is cached for each class, because the attribute never changes while the process is running.
     *
     * @return Translatable
     */
    private static function translation(): Translatable
    {
        $cache = &static::caches();
        $class = static::class;
        if (!isset($cache['translation'][$class])) {
            $translatable = null;
            for ($rc = new \ReflectionClass($class); $rc !== false && $translatable === null; $rc = $rc->getParentClass()) {
                $translatable = ($rc->getAttributes(Translatable::class)[0] ?? null)?->newInstance();
            }
            $cache['translation'][$class] = $translatable ?? new Translatable();
        }
        return $cache['translation'][$class];
    }

    /**
     * Get the reference of the caches of this class. (the translation setting, the metadata and the field index)
     *
     * All static caches of this trait are gathered here so that reset() can clear them.
     * Note: The static variable of the trait method is separated for each using class, so the caches are managed for each using class.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function &caches(bool $reset = false): array
    {
        static $CACHES = ['translation' => [], 'metadata' => [], 'index' => []];

        if ($reset) {
            $CACHES = ['translation' => [], 'metadata' => [], 'index' => []];
        }
        return $CACHES;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public static function reset(): void
    {
        static::caches(true);
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
        return $this->original('value');
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
     * Get the label.
     *
     * This is the same as translate('label', $locale), so that the label is translated only when 'label' is given to the Translatable attribute.
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
     * The field that is given to the Translatable attribute is translated by the translator of the group of the attribute,
     * and the field value (converted to string by Strings::stringify()) is returned when the translation is not found.
     * The field that is not given to the attribute (and the class without the attribute) is not translated at all, that is
     * the same as the field value that is converted to string. Use original() if you need the value as it is.
     *
     * @param  string         $field
     * @param  string|null    $locale (default: depend on configure)
     * @return string
     * @throws LogicException when the given field is not defined
     */
    #[Override]
    public function translate(string $field, string|null $locale = null): string
    {
        $value        = $this->original($field);
        $translatable = static::translation();
        if (!$translatable->has($field)) {
            return Strings::stringify($value);
        }
        $key        = get_called_class() . ".{$field}.{$this->value()}";
        $translated = Translator::get($translatable->group . ".{$key}", [], null, true, $locale);
        return $translated ?? Strings::stringify($value);
    }

    /**
     * Get the original field value or the metadata of this case. (the value before the translation)
     *
     * The value is resolved in the following order.
     *   1. The metadata of the name defined by the Meta attribute.
     *   2. The property of the name exists then the value of it is returned.
     *   3. The case name if the name is 'value' or 'label'.
     *
     * The value is never translated and the type is kept, even if the field is given to the Translatable attribute.
     * Use translate() or label() if you need the translated value.
     * (e.g. the original value is the fallback of the translation, and it is useful for the output that does not depend on the locale)
     *
     * @param  string         $field
     * @return mixed
     * @throws LogicException when the given field is not defined
     */
    #[Override]
    public function original(string $field): mixed
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

        // The metadata is checked first because it is cached and the main use case is the extension of the native enum (Meta never conflicts with the properties)
        $meta = $this->metadata();
        if ($meta->has($field)) {
            return $meta->get($field);
        }
        if (!($this instanceof \UnitEnum) && Reflector::reflectProperty($this, $field)?->isPublic() === true) {
            return $this->{$field};
        }
        if ($field === 'value' || $field === 'label') {
            return $this->name;
        }
        throw new LogicException("Undefined field or meta. " . static::class . "::{$this->name} has no field or meta '{$field}'.");
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function has(string $field): bool
    {
        return $field === 'name'
            || $field === 'value'
            || $field === 'label'
            || $this->metadata()->has($field)
            || (!($this instanceof \UnitEnum) && Reflector::reflectProperty($this, $field)?->isPublic() === true);
    }

    /**
     * Check this enum equals given value.
     * The value is compared as the array key by Arrays::tryAsKey(). (e.g. 1 equals '1' and true, same as valueOf()), and float, null, array and the other object never equal.
     * The same class instance is compared by the array key (Arrays::asKey() of itself), so that the instances of the non-native-enum class (e.g. database master reference) are also comparable.
     * (the key of the pure enum is the name)
     *
     * @param  mixed $value
     * @return bool
     */
    #[Override]
    public function equals($value): bool
    {
        if ($value instanceof static) {
            return Arrays::asKey($this) === Arrays::asKey($value);
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
     * @return Meta
     */
    private function metadata(): Meta
    {
        if (!($this instanceof \UnitEnum)) {
            static $EMPTY = null;
            return $EMPTY ??= new Meta();
        }
        $cache = &static::caches();
        $class = static::class;
        if (!isset($cache['metadata'][$class][$this->name])) {
            $attribute                              = (new \ReflectionEnumUnitCase($class, $this->name))->getAttributes(Meta::class)[0] ?? null;
            $cache['metadata'][$class][$this->name] = $attribute?->newInstance() ?? new Meta();
        }
        return $cache['metadata'][$class][$this->name];
    }

    /**
     * Get the field value that is translated if the field is given to the Translatable attribute.
     *
     * - The field given to the attribute : The translated value as a string. (same as translate())
     * - The other field                  : The field value as it is. (same as original())
     *
     * This is the common rule of the method call of the field, fieldOf(), listOf() and nextOf().
     *
     * @param  string         $field
     * @param  string|null    $locale (default: depend on configure)
     * @return mixed
     * @throws LogicException when the given field is not defined
     */
    private function resolve(string $field, string|null $locale = null): mixed
    {
        return static::translation()->has($field) ? $this->translate($field, $locale) : $this->original($field);
    }

    /**
     * Provides metadata access via method call. (e.g. `$status->color()`)
     *
     * If the field is given to the Translatable attribute then the translated value is returned (same as translate($field)),
     * otherwise the field value as it is is returned (same as original($field)).
     * The locale can be specified by the first argument or the named argument 'locale' (e.g. `$status->label_short('en')`).
     * The locale is used only when the field is translated, and it is ignored for the field that is not given to the attribute (no notice).
     * The other arguments are ignored.
     *
     * Note: Native enum can not have magic method __get, so we can not access the metadata as property.
     *
     * @param  string         $field
     * @param  array<mixed>   $args  [string|null $locale = null]
     * @return mixed
     * @throws LogicException when the given field is not defined
     * @throws \TypeError     when the locale is not string or null
     */
    public function __call(string $field, array $args): mixed
    {
        $locale = $args[0] ?? $args['locale'] ?? null;
        if ($locale !== null && !is_string($locale)) {
            throw new \TypeError(static::class . "::{$field}(): Argument #1 (\$locale) must be of type ?string, " . get_debug_type($locale) . " given");
        }
        return $this->resolve($field, $locale);
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
     * The string conversion returns the label (translated if the label is given to the Translatable attribute), the same as Reflector::convert().
     * The other types are converted from the value() (e.g. int for the int value), and null is returned if it is not convertible.
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
            $type === 'string'               => $this->label(),
            Reflector::typeOf($value, $type) => $value,
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
     * If the field is given to the Translatable attribute then the translated value is used as the key (the locale can be specified),
     * otherwise the field value as it is is used. The translated values may be duplicated, then the last enum wins and the others are dropped.
     *
     * @param  string                         $field   (default: 'value')
     * @param  (callable(T $enum): bool)|null $matcher the condition of enum to be included in the map (default: null)
     * @param  string|null                    $locale  (default: depend on configure)
     * @return array<int|string, T>
     * @throws LogicException                 when the given field is not defined or the field value can not be used as the array key (e.g. array, object)
     */
    #[Override]
    public static function maps(string $field = 'value', callable|null $matcher = null, string|null $locale = null): array
    {
        $maps = [];
        foreach (static::lists($matcher) as $enum) {
            $key = Arrays::tryAsKey($enum->resolve($field, $locale));
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
     * The value is compared as the array key by Arrays::tryAsKey(). (e.g. '1' and true match 1)
     * The value that can not be used as the array key (e.g. array) matches nothing.
     *
     * If the field is given to the Translatable attribute then the translated value is compared (the locale can be specified),
     * otherwise the field value as it is is compared. (the index of the native enum is cached only in this case)
     *
     * @param  string         $field
     * @param  mixed          $value
     * @param  string|null    $locale (default: depend on configure)
     * @return T|null
     * @throws LogicException
     */
    #[Override]
    public static function fieldOf(string $field, $value, string|null $locale = null): self|null
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
        if (!static::translation()->has($field)) {
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
     * Get a list of given field/meta of the given enums. (translated if the field is given to the Translatable attribute)
     *
     * @param  array<int, T>     $enums
     * @param  string            $field
     * @param  string|null       $locale (default: depend on configure)
     * @return array<int, mixed>
     * @throws LogicException    when the given field is not defined
     */
    private static function pluck(array $enums, string $field, string|null $locale): array
    {
        $values = [];
        foreach ($enums as $enum) {
            $values[] = $enum->resolve($field, $locale);
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
        $cache = &static::caches();
        $class = static::class;
        if (isset($cache['index'][$class][$field])) {
            return $cache['index'][$class][$field];
        }

        $index = [];
        foreach (static::lists() as $enum) {
            if (($key = Arrays::tryAsKey($enum->original($field))) !== null) {
                $index[$key] = $enum;
            }
        }
        if (enum_exists($class)) {
            $cache['index'][$class][$field] = $index;
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
     * The label is compared with the label that is translated if 'label' is given to the Translatable attribute.
     * If there is an enum with the same filed value, it wins after enum::lists().
     *
     * @param  string      $label
     * @param  string|null $locale (default: depend on configure)
     * @return T|null
     */
    #[Override]
    public static function labelOf(string $label, string|null $locale = null): self|null
    {
        return static::fieldOf('label', $label, $locale);
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
     * The field/meta is resolved by original() so that the property and the metadata defined by the Meta attribute are available.
     * The field that is given to the Translatable attribute is translated (the locale can be specified), otherwise the field value as it is is returned.
     *
     * @param  string                         $field
     * @param  (callable(T $enum): bool)|null $matcher (default: null)
     * @param  string|null                    $locale  (default: depend on configure)
     * @return array<int, mixed>
     * @throws LogicException                 when the given field is not defined
     */
    #[Override]
    public static function listOf(string $field, callable|null $matcher = null, string|null $locale = null): array
    {
        return static::pluck(static::lists($matcher), $field, $locale);
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
        return static::listOf('label', $matcher, $locale);
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
     * The field/meta is resolved by original() so that the property and the metadata defined by the Meta attribute are available.
     * The field that is given to the Translatable attribute is translated (the locale can be specified), otherwise the field value as it is is returned.
     *
     * @param  string            $field
     * @param  T|V|null          $current
     * @param  array<mixed>|null $context (default: null)
     * @param  string|null       $locale  (default: depend on configure)
     * @return array<int, mixed>
     * @throws LogicException    when the given field is not defined
     */
    #[Override]
    public static function nextOf(string $field, mixed $current, array|null $context = null, string|null $locale = null): array
    {
        return static::pluck(static::nexts($current, $context), $field, $locale);
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
        return static::nextOf('label', $current, $context, $locale);
    }
}
