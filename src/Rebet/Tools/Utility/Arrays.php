<?php
namespace Rebet\Tools\Utility;

use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Reflection\Reflector;

/**
 * Array Utility Class
 *
 * Some fonctions implementation are borrowed from Illuminate\Support\Arr and Collection of laravel/framework ver 5.7 with some modifications.
 *
 * @see https://github.com/laravel/framework/blob/5.7/src/Illuminate/Support/Arr.php
 * @see https://github.com/laravel/framework/blob/5.7/src/Illuminate/Support/Collection.php
 * @see https://github.com/laravel/framework/blob/5.7/LICENSE.md
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Arrays
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * It randomly selects the given number of items from the target list.
     *
     * ex)
     * [$winner, $loser] = Arrays::random($lottery_applicants, 3);
     *
     * @param array $list
     * @param int $number
     * @return array [[selected_item, ...], [not_selected_items, ...]]
     */
    public static function random(array $list, int $number) : array
    {
        if (count($list) <= $number) {
            return [$list, []] ;
        }

        $selected   = [];
        $max_idx    = count($list) - 1;
        $i          = 0;
        $missselect = 0;
        shuffle($list);
        while ($i < $number) {
            $idx = mt_rand(0, $max_idx);
            if (isset($selected[$idx])) {
                if ($missselect++ < 10) {
                    continue;
                }
                break;
            }
            $selected[$idx] = $list[$idx];
            unset($list[$idx]);
            $i++;
            $missselect = 0;
        }
        $selected = array_merge($selected);

        while ($i < $number) {
            shuffle($list);
            $idx        = mt_rand(0, count($list) - 1);
            $selected[] = $list[$idx];
            unset($list[$idx]);
            $i++;
        }

        return [$selected, array_merge($list)];
    }

    /**
     * It checks whether the specified array is a sequential number array starting at index = 0.
     * #If null was given, return false.
     *
     * ex)
     * Arrays::isSequential([]);                             //=> true
     * Arrays::isSequential([1,2,3]);                        //=> true
     * Arrays::isSequential([0 => 'a', '1' => 'b']);         //=> true
     * Arrays::isSequential([0 => 'a', 2 => 'c', 1 => 'b']); //=> false
     * Arrays::isSequential([1 => 'c', 2 => 'b']);           //=> false
     * Arrays::isSequential(['a' => 'a', 'b' => 'b']);       //=> false
     *
     * @param  array|null $array
     * @return bool
     */
    public static function isSequential($array) : bool
    {
        if (!static::accessible($array)) {
            return false;
        }
        $i = 0;
        foreach ($array as $key => $value) {
            if ($key !== $i++) {
                return false;
            }
        }
        return true;
    }

    /**
     * Pluck an array of values from an array.
     * # You can use dot access of Reflector::get() in $key_field and $value_field.
     * # You can use Closure of function($i, $k, $v) { ... } for extract key and value. ($i: index number of array, $k: key of array, $v: value of array)
     *
     * ex)
     * $user_ids   = Arrays::pluck($users, 'user_id'                                                 , null                                        ); //=> [21, 35, 43, ...]
     * $user_ids   = Arrays::pluck($users, 'user_id'                                                 , function($i, $k, $v) { return "user_{$i}"; }); //=> ['user_1' => 21, 'user_2' => 35, 'user_3' => 43, ...]
     * $user_names = Arrays::pluck($users, 'name'                                                    , 'user_id'                                   ); //=> [21 => 'John', 35 => 'David', 43 => 'Linda', ...]
     * $user_banks = Arrays::pluck($users, 'bank.name'                                               , 'user_id'                                   ); //=> [21 => 'City', 35 => 'JPMorgan', 43 => 'Montreal', ...]
     * $user_map   = Arrays::pluck($users, null                                                      , 'user_id'                                   ); //=> [21 => <User object>, 35 => <User object>, 43 => <User object>, ...]
     * $user_map   = Arrays::pluck($users, function($i, $k, $v) { return "{$v->name}($v->user_id)"; }, 'user_id'                                   ); //=> [21 => 'John(21)', 35 => 'David(35)', 43 => 'Linda(43)', ...]
     *
     * @param array|null $list
     * @param int|string|\Closure|null $value_field Field name / index / extract function as the value of extracted data (Row element itself is targeted when blank is specified)
     * @param int|string|\Closure|null $key_field Field name / index / extract function as key of extracted data (It becomes serial number array when blank is specified)
     * @return array
     * @see Reflector::get()
     */
    public static function pluck(?array $list, $value_field, $key_field = null) : array
    {
        if (Utils::isEmpty($list)) {
            return [];
        }
        $plucks = [];
        $i      = 0;
        foreach ($list as $key => $row) {
            $k          = Utils::isBlank($key_field)   ? $i   : ($key_field   instanceof \Closure ? call_user_func($key_field, $i, $key, $row)   : Reflector::get($row, $key_field));
            $plucks[$k] = Utils::isBlank($value_field) ? $row : ($value_field instanceof \Closure ? call_user_func($value_field, $i, $key, $row) : Reflector::get($row, $value_field));
            $i++;
        }
        return $plucks;
    }

    /**
     * Merge / overwrite with a differences map to the base map.
     *
     * This method differs from 'array_merge' in that it merges / overwrites recursively when the value is a map,
     * And it differs from 'array_merge_recursive' in that it overwrites the value if the value is an object.
     *
     * Specifically, it behaves as follows.
     *
     * Arrays::override(
     *     [
     *         'map_map'     => ['a' => 'a', 'b' => 'b'],
     *         'array_array' => ['a', 'b'],
     *         'map_array'   => ['a' => 'a', 'b' => 'b'],
     *         'array_map'   => ['c'],
     *         'other'       => 'a',
     *         'left'        => 'a',
     *     ],
     *     [
     *         'map_map'     => ['a' => 'A', 'c' => 'C'],
     *         'array_array' => ['c'],
     *         'map_array'   => ['c'],
     *         'array_map'   => ['a' => 'a', 'b' => 'b'],
     *         'other'       => 'A',
     *         'added'       => 'added',
     *     ],
     * );
     * => [
     *     'map_map'     => ['a' => 'A', 'b' => 'b', 'c' => 'C'],
     *     'array_array' => ['a', 'b', 'c'],
     *     'map_array'   => ['c'],
     *     'array_map'   => ['a' => 'a', 'b' => 'b'],
     *     'other'       => 'A',
     *     'left'        => 'a',
     *     'added'       => 'added',
     * ]
     *
     * This behavior can be changed by specifying the option below.
     *
     * 　- The merge behavior of Map or Array can be changed to merge             by OverrideOption::MERGE   (= '+').
     * 　- The merge behavior of Map or Array can be changed to replacement       by OverrideOption::REPLACE (= '=').
     * 　- The merge behavior of        Array can be changed to forward addition  by OverrideOption::PREPEND (= '<').
     * 　- The merge behavior of        Array can be changed to backward addition by OverrideOption::APPEND  (= '>').
     *
     * Also, regarding the behavior of an array, you can change the default overall behavior by specifying the above mode for $default_array_override_option.
     *
     * Arrays::override(
     *     [
     *         'map'   => ['a' => ['A' => 'A'], 'b' => 'b'],
     *         'array' => ['a', 'b'],
     *     ],
     *     [
     *         'map'   => ['a' => ['B' => 'B'], 'c' => 'C'],
     *         'array' => ['c'],
     *     ],
     *     [
     *         'map'   => ['a' => OverrideOption::REPLACE],
     *         'array' => OverrideOption::PREPEND,
     *     ]
     * );
     * => [
     *     'map'   => ['a' => ['B' => 'B'], 'b' => 'b', 'c' => 'C'],
     *     'array' => ['c', 'a', 'b'],
     * ]
     *
     * The above code can also be specified by giving '+', '=', '<', '>' at the end of the difference map key name as shown below.
     *
     * Arrays::override(
     *     [
     *         'map'    => ['a' => ['A' => 'A'], 'b' => 'b'],
     *         'array'  => ['a', 'b'],
     *     ],
     *     [
     *         'map'    => ['a=' => ['B' => 'B'], 'c' => 'C'],
     *         'array<' => ['c'],
     *     ]
     * );
     *
     * This way makes it easier to change the override behavior,
     * but it is only supported in the $option that you can specify wild mark '*' that matches multiple keys.
     *
     * Arrays::override(
     *     [
     *         'foo' => ['a' => ['bar' => 'a'], 'b' => ['bar' => 'b']],
     *     ],
     *     [
     *         'foo' => ['a' => ['bar' => 'A'], 'b' => ['bar' => 'B']],
     *     ],
     *     [
     *         'foo' => ['*' => ['bar' => OverrideOption::REPLACE]],
     *     ]
     * );
     *
     * @see OverrideOption
     *
     * @param mixed $base
     * @param mixed $diff
     * @param array|string $option
     * @param string $default_array_override_option (default: OverrideOption::APPEND)
     * @param \Closure $handler of special override logic(if return null then do nothing). function($base, $diff, $option, $default_array_override_option):mixed (default: null)
     * @return mixed
     */
    public static function override($base, $diff, $option = [], string $default_array_override_option = OverrideOption::APPEND, ?\Closure $handler = null)
    {
        if ($handler) {
            if ($value = $handler($base, $diff, $option, $default_array_override_option)) {
                return $value;
            }
        }

        if (!static::accessible($base) || !static::accessible($diff) || $option === OverrideOption::REPLACE) {
            return $diff;
        }

        $is_base_sequential = static::isSequential($base);
        $is_diff_sequential = static::isSequential($diff);
        $apply_option       = \is_string($option) ? $option : $default_array_override_option ;
        if ($is_base_sequential && $is_diff_sequential && $apply_option !== OverrideOption::MERGE) {
            return static::arrayMerge($base, $diff, $apply_option);
        }

        if ($is_base_sequential !== $is_diff_sequential && $apply_option !== OverrideOption::MERGE) {
            return empty($diff) ? $base : $diff ;
        }

        foreach ($diff as $key => $value) {
            [$key, $apply_option] = OverrideOption::split($key);
            $apply_option         = $apply_option ?? (is_array($option) ? ($option[$key] ?? $option['*'] ?? null) : null) ;
            if (isset($base[$key])) {
                $base[$key] = static::override($base[$key], $value, $apply_option, $default_array_override_option, $handler);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Merge the sequential number array according to the option contents.
     *
     * @param array|\ArrayAccess $base
     * @param array|\ArrayAccess $diff
     * @param string $option
     * @return mixed
     */
    private static function arrayMerge($base, $diff, string $option)
    {
        switch ($option) {
            case OverrideOption::PREPEND:
                // Keep the class of given '$base'.
                $merged = is_array($base) ? [] : clone $base ;
                $index  = 0;
                foreach ($diff as $value) {
                    $merged[$index++] = $value;
                }
                foreach ($base as $value) {
                    $merged[$index++] = $value;
                }
                return $merged;
            case OverrideOption::APPEND:
                foreach ($diff as $value) {
                    $base[] = $value;
                }
                return $base;
            case OverrideOption::REPLACE:
                return $diff;
        }

        throw new LogicException("Invalid array merge mode '{$option}' given.");
    }

    /**
     * Get a list of duplicate values from the given array.
     *
     * @param array|null $array
     * @return array|null
     */
    public static function duplicate(?array $array) : ?array
    {
        if ($array === null) {
            return null;
        }

        $duplicate = [];
        foreach (array_count_values($array) as $value => $count) {
            if (1 < $count) {
                $duplicate[] = $value;
            }
        }
        return $duplicate;
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  array|null  $array
     * @return array|null
     */
    public static function collapse($array) : ?array
    {
        if ($array === null) {
            return null;
        }

        $results = [];
        foreach ($array as $values) {
            if (! is_array($values)) {
                continue;
            }
            $results = array_merge($results, $values);
        }
        return $results;
    }

    /**
     * Cross join the given arrays, returning all possible permutations.
     *
     * @param  iterable  ...$arrays
     * @return array
     */
    public static function crossJoin(iterable ...$arrays) : array
    {
        $results = [[]];
        foreach ($arrays as $index => $array) {
            $append = [];
            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;
                    $append[]        = $product;
                }
            }
            $results = $append;
        }
        return $results;
    }

    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || ($value instanceof \ArrayAccess && $value instanceof \Traversable && $value instanceof \Countable);
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  \ArrayAccess|array|null  $array
     * @param  string|int|null  $key
     * @return bool
     */
    public static function exists($array, $key) : bool
    {
        if ($array === null) {
            return false;
        }
        if ($array instanceof \ArrayAccess) {
            return $array->offsetExists($key);
        }
        return array_key_exists($key, $array);
    }

    /**
     * Get all of the given array except for a specified array of keys.
     *
     * @param  array|null  $array
     * @param  array|string  $keys
     * @return array|null
     */
    public static function except(?array $array, $keys)
    {
        if ($array === null) {
            return null;
        }
        static::forget($array, $keys);
        return $array;
    }

    /**
     * Remove one array items from a given array and get it.
     *
     * @param array|null $array
     * @param int|string $key
     * @param mixed $default (default: null)
     * @return mixed
     */
    public static function remove(?array &$array, $key, $default = null)
    {
        $value = $array[$key] ?? $default;
        unset($array[$key]);
        return $value;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array|unll  $array
     * @param  array|string  $keys
     * @return void
     */
    public static function forget(?array &$array, $keys)
    {
        if ($array === null) {
            return;
        }

        $original = &$array;
        $keys     = (array) $keys;
        if (count($keys) === 0) {
            return;
        }
        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);
                continue;
            }
            $parts = explode('.', $key);
            // clean up before each pass
            $array = &$original;
            while (count($parts) > 1) {
                $part = array_shift($parts);
                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }
            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Filter the array using the given callback.
     *
     * @param  array|null  $array
     * @param  callable|null $callback of function($value [, $key]):bool or null that return given array as it is.
     * @return array
     */
    public static function where(?array $array, ?callable $callback)
    {
        if ($array === null) {
            return null;
        }
        return $callback === null ? $array : array_filter($array, $callback, ARRAY_FILTER_USE_BOTH) ;
    }

    /**
     * Remove blank values from given array.
     *
     * @see Utils::isBlank()
     * @param array|null $array
     * @return array|null
     */
    public static function compact(?array $array) : ?array
    {
        if ($array === null) {
            return null;
        }
        return array_filter($array, function ($value) { return !Utils::isBlank($value); });
    }

    /**
     * Remove duplicate values from given array.
     *
     * @param array|null $array
     * @param int $sort_flags (default: SORT_REGULAR)
     * @return array|null
     */
    public static function unique(?array $array, int $sort_flags = SORT_REGULAR) : ?array
    {
        return $array === null ? null : array_unique($array, $sort_flags) ;
    }

    /**
     * Return the key in an array passing a given truth test.
     *
     * @param  array|null  $array
     * @param  callable  $callback
     * @param  mixed  $default (default: null)
     * @return mixed
     */
    public static function find($array, callable $callback, $default = null)
    {
        if ($array === null) {
            return static::value($default);
        }
        foreach ($array as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $key;
            }
        }
        return static::value($default);
    }

    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param  array|null  $array
     * @param  callable|null  $callback function($value, $key) : bool {...}
     * @param  mixed  $default (default: null)
     * @return mixed
     */
    public static function first($array, ?callable $callback = null, $default = null)
    {
        if ($array === null) {
            return static::value($default);
        }
        if (is_null($callback)) {
            if (empty($array)) {
                return static::value($default);
            }
            foreach ($array as $item) {
                return $item;
            }
        }
        foreach ($array as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $value;
            }
        }
        return static::value($default);
    }

    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    private static function value($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  array|null  $array
     * @param  int  $depth (default: INF)
     * @return array
     */
    public static function flatten(?array $array, $depth = INF) : ?array
    {
        if ($array === null) {
            return null;
        }
        $result = [];
        foreach ($array as $item) {
            $item = static::accessible($item) ? static::toArray($item) : $item ;
            if (! is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, array_values($item));
            } else {
                $result = array_merge($result, static::flatten($item, $depth - 1));
            }
        }
        return $result;
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param  array|null  $array
     * @param  callable|null  $callback function($value, $key) : bool {...}
     * @param  mixed  $default
     * @return mixed
     */
    public static function last(?array $array, ?callable $callback = null, $default = null)
    {
        if ($array === null) {
            return null;
        }
        if (is_null($callback)) {
            return empty($array) ? static::value($default) : end($array);
        }
        return static::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @param  array|ull  $array
     * @param  array|string  $keys
     * @return array
     */
    public static function only(?array $array, $keys) : ?array
    {
        if ($array === null) {
            return null;
        }
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Push an item onto the beginning of an array.
     *
     * @param  array|null  $array
     * @param  mixed  $value
     * @param  mixed  $key
     * @return array
     */
    public static function prepend(?array $array, $value, $key = null) : array
    {
        if ($array === null) {
            return is_null($key) ? [$value] : [$key => $value] ;
        }
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }
        return $array;
    }

    /**
     * Get a value from the array, and remove it.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function pull(&$array, $key, $default = null)
    {
        $value = Reflector::get($array, $key, $default);
        static::forget($array, $key);
        return $value;
    }

    /**
     * Shuffle the given array and return the result.
     *
     * @param  array|null  $array
     * @param  int|null  $seed
     * @return array
     */
    public static function shuffle(?array $array, ?int $seed = null) : ?array
    {
        if ($array === null) {
            return null;
        }
        if (is_null($seed)) {
            shuffle($array);
        } else {
            srand($seed);
            usort($array, function () {
                return rand(-1, 1);
            });
        }
        return $array;
    }

    /**
     * Count items of given value.
     *
     * @param mixed $value
     * @param callable|null $test of counting target function($value, $key):bool
     * @return int
     */
    public static function count($value, ?callable $test = null) : int
    {
        if ($test === null && (is_array($value) || $value instanceof \Countable)) {
            return count($value);
        }
        if (is_iterable($value)) {
            $count = 0;
            foreach ($value as $key => $item) {
                $count += $test === null ? 1 : (call_user_func($test, $item, $key) ? 1 : 0) ;
            }
            return $count;
        }
        return $value === null ? 0 : 1 ;
    }

    /**
     * Convert to array from Arrayable.
     *
     * @param  mixed  $items
     * @return array|null
     */
    public static function toArray($items) : ?array
    {
        if ($items === null) {
            return null;
        }
        if (is_array($items)) {
            return $items;
        }
        if (method_exists($items, 'toArray')) {
            return $items->toArray();
        }
        if ($items instanceof \JsonSerializable) {
            if (is_array($json = $items->jsonSerialize())) {
                return $json;
            }
        }
        if (is_iterable($items)) {
            return iterator_to_array($items);
        }
        if (is_string($items)) {
            if (is_array($json = json_decode($items))) {
                return $json;
            }
        }

        return [$items];
    }

    /**
     * Run a map over each of the items.
     *
     * @param array|null $array
     * @param  callable  $callback function($value, $key) { ... }
     * @return array|null
     */
    public static function map(?array $array, callable $callback) : ?array
    {
        if ($array === null) {
            return null;
        }
        $keys = array_keys($array);
        return array_combine($keys, array_map($callback, $array, $keys));
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param array|null $array
     * @param callable $reducer function($carry, $item) { ... }
     * @param mixed $initial (defualt: null)
     * @return mixed
     */
    public static function reduce(?array $array, callable $reducer, $initial = null)
    {
        return $array === null ? null : array_reduce($array, $reducer, $initial) ;
    }

    /**
     * Get the items in the collection that are not present in the given items.
     *
     * @param  array|null $array
     * @param  mixed $items
     * @param  callable|null $comparator function(mixed $a, mixed $b) : int (default: null)
     * @return array|null
     */
    public static function diff(?array $array, $items, ?callable $comparator = null) : ?array
    {
        if ($array === null) {
            return null;
        }
        return $comparator
            ? array_udiff($array, static::toArray($items) ?? [], $comparator)
            : array_diff($array, static::toArray($items) ?? [])
            ;
    }

    /**
     * Intersect the collection with the given items.
     *
     * @param array|null $array
     * @param mixed $items
     * @param callable|null $comparator function(mixed $a, mixed $b):int (default: null)
     * @return array|null
     */
    public static function intersect(?array $array, $items, ?callable $comparator = null) : ?array
    {
        if ($array === null) {
            return null;
        }
        return $comparator
            ? array_uintersect($array, static::toArray($items) ?? [], $comparator)
            : array_intersect($array, static::toArray($items) ?? [])
            ;
    }

    /**
     * Determine if all items in the collection pass the given test.
     *
     * @param array $array
     * @param callable $test of function($v, $k):bool
     * @return bool
     */
    public static function every(?array $array, callable $test) : bool
    {
        if ($array === null) {
            return true;
        }
        foreach ($array as $k => $v) {
            if (!$test($v, $k)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Group an associative array by a field or using a callback.
     *
     * @param  array|null $array
     * @param  callable|string|array $group_by (default: null)
     * @param  bool  $preserve_keys (default: false)
     * @return array
     */
    public static function groupBy(?array $array, $group_by = null, bool $preserve_keys = false) : ?array
    {
        if ($array === null) {
            return null;
        }
        if (is_array($group_by)) {
            $next_groups = $group_by;
            $group_by    = array_shift($next_groups);
        }
        $group_by = Callbacks::retriever($group_by) ;
        $results  = [];
        foreach ($array as $key => $value) {
            $group_keys = $group_by($value, $key);
            if (! is_array($group_keys)) {
                $group_keys = [$group_keys];
            }
            foreach ($group_keys as $group_key) {
                $group_key = is_bool($group_key) ? (int) $group_key : $group_key;
                if (! array_key_exists($group_key, $results)) {
                    $results[$group_key] = [];
                }
                if ($preserve_keys) {
                    $results[$group_key][$key] = $value;
                } else {
                    $results[$group_key][] = $value;
                }
            }
        }
        if (! empty($next_groups)) {
            return static::map($results, function ($array) use ($next_groups, $preserve_keys) { return static::groupBy($array, $next_groups, $preserve_keys); });
        }
        return $results;
    }

    /**
     * Union the collection with the given items.
     *
     * @param array|null $array
     * @param mixed $other
     * @return array
     */
    public static function union(?array $array, $other) : ?array
    {
        return $array === null ? null : $array + (static::toArray($other) ?? []);
    }

    /**
     * Get the min value of a given key or value retriever.
     * Note: If you want to use the key name same as php function, you can use the key name with '@' prefix.
     *
     * @param array|null $array
     * @param callable|string|null $retriever key name (with/without '@') or function($value):mixed. (default: null)
     * @param mixed $initial (default: null)
     * @return mixed
     */
    public static function min(?array $array, $retriever = null, $initial = null)
    {
        $retriever = Callbacks::retriever($retriever);
        $reducer   = function ($carry, $value) use ($retriever) {
            return $carry === null || $retriever($value) < $retriever($carry) ? $value : $carry ;
        };
        return static::reduce($array, $reducer, $initial);
    }

    /**
     * Get the max value of a given key or value retriever.
     * Note: If you want to use the key name same as php function, you can use the key name with '@' prefix.
     *
     * @param array|null $array
     * @param callable|string|null $retriever key name (with/without '@') or function($value):mixed. (default: null)
     * @param mixed $initial (default: null)
     * @return mixed
     */
    public static function max(?array $array, $retriever = null, $initial = null)
    {
        $retriever = Callbacks::retriever($retriever);
        $reducer   = function ($carry, $value) use ($retriever) {
            return $carry === null || $retriever($value) > $retriever($carry) ? $value : $carry ;
        };
        return static::reduce($array, $reducer, $initial);
    }

    /**
     * Sort the array using the given comparator or sort flag.
     *
     * @param array|null $array
     * @param int $order (default: SORT_ASC)
     * @param callable|int $comparator of sort flag or function($a, $b):int callable (default: SORT_REGULAR)
     * @return array|null
     */
    public static function sort(?array $array, int $order = SORT_ASC, $comparator = SORT_REGULAR) : ?array
    {
        if ($array === null) {
            return null;
        }

        if (is_int($comparator)) {
            $order === SORT_ASC ? asort($array, $comparator) : arsort($array, $comparator) ;
            return $array;
        }

        if (is_callable($comparator)) {
            $sorter = $order === SORT_ASC ? $comparator : function ($a, $b) use ($comparator) { return call_user_func($comparator, $a, $b) * -1; };
            uasort($array, $sorter);
            return $array;
        }

        return $array;
    }

    /**
     * Sort the collection using the given key or value retriever.
     *
     * @param array|null $array
     * @param callable|string $retriever key name (with/without '@') or function($value):mixed.
     * @param int $order (default: SORT_ASC)
     * @param callable|int $comparator of sort flag or function($a, $b):int callable (default: SORT_REGULAR)
     * @return array
     */
    public static function sortBy(?array $array, $retriever, int $order = SORT_ASC, $comparator = SORT_REGULAR) : ?array
    {
        if ($array === null) {
            return null;
        }

        $comparator = !is_int($comparator) ? $comparator : function ($a, $b) use ($comparator) {
            $array = [$a, $b];
            sort($array, $comparator);
            return $array[0] === $a ? -1 : 1 ;
        };

        $retriever = Callbacks::retriever($retriever);
        $sorter    = function ($a, $b) use ($retriever, $comparator) {
            return call_user_func($comparator, $retriever($a), $retriever($b));
        };

        return static::sort($array, $order, $sorter);
    }

    /**
     * Sort the array keys using the given comparator or sort flag.
     *
     * @param array|null $array
     * @param int $order (default: SORT_ASC)
     * @param callable|int $comparator of sort flag or function($a, $b):int callable (default: SORT_REGULAR)
     * @return array|null
     */
    public static function sortKeys(?array $array, int $order = SORT_ASC, $comparator = SORT_REGULAR) : ?array
    {
        if ($array === null) {
            return null;
        }

        if (is_int($comparator)) {
            $order === SORT_ASC ? ksort($array, $comparator) : krsort($array, $comparator) ;
            return $array;
        }

        if (is_callable($comparator)) {
            $sorter = $order === SORT_ASC ? $comparator : function ($a, $b) use ($comparator) { return call_user_func($comparator, $a, $b) * -1; };
            uksort($array, $sorter);
            return $array;
        }

        return $array;
    }

    /**
     * Get the sum of the given values.
     *
     * @param array|null $array
     * @param callable|string|null $retriever key name (with/without '@') or function($value):mixed. (default: null)
     * @param bool $arbitrary_precision (default: false)
     * @param int|null $precision for arbitrary precision (default: null)
     * @return Decimal|null
     */
    public static function sum(?array $array, $retriever = null, bool $arbitrary_precision = false, ?int $precision = null) : ?Decimal
    {
        if ($array === null) {
            return null;
        }

        if ($retriever === null && !$arbitrary_precision) {
            return Decimal::of(array_sum($array));
        }

        $retriever = Callbacks::retriever($retriever);
        return Decimal::of(static::reduce($array, function ($carry, $item) use ($retriever, $arbitrary_precision, $precision) {
            return $arbitrary_precision ? Decimal::of($carry)->add($retriever($item) ?? '0', $precision) : $carry + ($retriever($item) ?? 0) ;
        }, '0'));
    }

    /**
     * Get the average of the given values.
     * Note: If the retriever return null then that item exclude from count. It means that you can calculate the selective average value.
     *
     * @param array|null $array
     * @param callable|string|null $retriever key name (with/without '@') or function($value):mixed.
     * @param bool $arbitrary_precision (default: false)
     * @param int|null $precision for arbitrary precision (default: null)
     * @return Decimal|null
     */
    public static function avg(?array $array, $retriever = null, bool $arbitrary_precision = false, ?int $precision = null) : ?Decimal
    {
        if (empty($array)) {
            return null;
        }
        $retriever = $retriever === null ? null : Callbacks::retriever($retriever) ;
        $counter   = $retriever === null ? null : function ($v) use ($retriever) { return call_user_func($retriever, $v) !== null; };
        $sum       = static::sum($array, $retriever, $arbitrary_precision, $precision);
        $count     = static::count($array, $counter);
        return $sum->div($count, $precision) ;
    }

    /**
     * Get the median of the given values.
     *
     * @param array|null $array
     * @param callable|string|null $retriever key name (with/without '@') or function($value):mixed.
     * @param bool $arbitrary_precision (default: false)
     * @param int|null $precision for arbitrary precision (default: null)
     * @return Decimal|null
     */
    public static function median(?array $array, $retriever = null, bool $arbitrary_precision = false, ?int $precision = null) : ?Decimal
    {
        if (empty($array)) {
            return null;
        }
        $array = $retriever === null ? $array : static::map($array, Callbacks::retriever($retriever));
        $array = array_values(static::sort(static::compact($array)));
        $count = static::count($array);
        if ($count == 0) {
            return null;
        }
        $middle = (int) ($count / 2);
        if ($count % 2) {
            return Decimal::of($array[$middle]);
        }
        return static::avg([$array[$middle - 1], $array[$middle]], null, $arbitrary_precision, $precision);
    }

    /**
     * Get the mode of a given key.
     *
     * @param array|null $array
     * @param callable|string|null $retriever key name (with/without '@') or function($value):mixed.
     * @return array|null
     */
    public static function mode(?array $array, $retriever = null) : ?array
    {
        if (empty($array)) {
            return null;
        }
        $array  = $retriever === null ? $array : static::map($array, Callbacks::retriever($retriever));
        $counts = static::map(static::groupBy(static::compact($array)), function ($v) { return static::count($v); });
        $counts = static::sort($counts);
        $count  = end($counts);
        return array_keys(static::where($counts, function ($v) use ($count) { return $v === $count; }));
    }

    /**
     * Peel the array blanket if the given array contains less equal one item.
     *
     * @param mixed $array
     * @return mixed
     */
    public static function peel($array)
    {
        if (!static::accessible($array)) {
            return $array;
        }
        $count = static::count($array);
        if ($count === 0) {
            return null;
        }
        if ($count === 1) {
            foreach ($array as $item) {
                return $item;
            }
        }
        return $array;
    }

    /**
     * Join the given array elements to string using given delimiter.
     *
     * @param mixed $iterable
     * @param string $delimiter (default: ', ')
     * @return string|null return null when other than iterable given as $iterable.
     */
    public static function implode($iterable, string $delimiter = ', ') : ?string
    {
        if (!is_iterable($iterable)) {
            return null;
        }

        $string = '';
        foreach ($iterable as $value) {
            if (is_iterable($value)) {
                $value = '['.static::implode($value, $delimiter).']';
            }
            $string .= "{$value}{$delimiter}";
        }
        return Strings::rtrim($string, $delimiter, 1);
    }

    /**
     * Pop the last [key, value] from given array.
     *
     * @param array $array
     * @return array ['key' => key, 'value' => value]
     */
    public static function pop(array &$array) : array
    {
        if (empty($array)) {
            return ['key' => null, 'value' => null];
        }
        $keys  = array_keys($array);
        $key   = end($keys);
        $value = $array[$key];
        unset($array[$key]);
        return ['key' => $key, 'value' => $value];
    }

    /**
     * Generate URL-encoded query string.
     *
     * @param array|\Traversable $value
     * @param integer $encoding of PHP_QUERY_* (default: PHP_QUERY_RFC1738)
     * @return string|null
     */
    public static function toQuery($value, int $encoding = PHP_QUERY_RFC1738) : ?string
    {
        return $value === null ? null : http_build_query($value, '', '&', $encoding) ;
    }
}
