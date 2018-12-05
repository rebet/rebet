<?php
namespace Rebet\Common;

/**
 * Array Utility Class
 *
 * Belows fonction implementation are borrowed from Illuminate\Support\Arr of laravel/framework ver 5.7 with some modifications.
 *
 *  - shuffle / pull / prepend / only / last / flatten / value (from helpers.php) / first
 *  - where / forget / except / exists / crossJoin / collapse / accessible
 *
 * @see https://github.com/laravel/framework/blob/5.7/src/Illuminate/Support/Arr.php
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
     *
     * ex)
     * $user_ids   = Arrays::pluck($users, 'user_id'  , null     ); //=> [21, 35, 43, ...]
     * $user_names = Arrays::pluck($users, 'name'     , 'user_id'); //=> [21 => 'John', 35 => 'David', 43 => 'Linda', ...]
     * $user_banks = Arrays::pluck($users, 'bank.name', 'user_id'); //=> [21 => 'City', 35 => 'JPMorgan', 43 => 'Montreal', ...]
     * $user_map   = Arrays::pluck($users, null       , 'user_id'); //=> [21 => <User object>, 35 => <User object>, 43 => <User object>, ...]
     *
     * @param array|null $list
     * @param int|string|null $key_field Field name / index as key of extracted data (It becomes serial number array when blank is specified)
     * @param int|string|null $value_field Field name / index as the value of extracted data (Row element itself is targeted when blank is specified)
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
        foreach ($list as $row) {
            $plucks[Utils::isBlank($key_field) ? $i++ : Reflector::get($row, $key_field)] = Utils::isBlank($value_field) ? $row : Reflector::get($row, $value_field);
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
     * 　- The merge behavior of Map or Array can be changed to replacement       by OverrideOption :: REPLACE (= '!').
     * 　- The merge behavior of        Array can be changed to forward addition  by OverrideOption :: PREPEND (= '<').
     * 　- The merge behavior of        Array can be changed to backward addition by OverrideOption :: APEND   (= '>').
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
     * The above code can also be specified by giving '!', '<', '>' At the end of the difference map key name as shown below.
     *
     * Arrays::override(
     *     [
     *         'map'    => ['a' => ['A' => 'A'], 'b' => 'b'],
     *         'array'  => ['a', 'b'],
     *     ],
     *     [
     *         'map'    => ['a!' => ['B' => 'B'], 'c' => 'C'],
     *         'array<' => ['c'],
     *     ]
     * );
     *
     * @see OverrideOption
     *
     * @param mixed $base
     * @param mixed $diff
     * @param array|string $option
     * @param string $default_array_override_option Default: OverrideOption::APEND
     * @return mixed
     */
    public static function override($base, $diff, $option = [], string $default_array_override_option = OverrideOption::APEND)
    {
        if (!static::accessible($base) || !static::accessible($diff) || $option === OverrideOption::REPLACE) {
            return $diff;
        }

        $is_base_sequential = static::isSequential($base);
        $is_diff_sequential = static::isSequential($diff);
        if ($is_base_sequential && $is_diff_sequential) {
            return static::arrayMerge($base, $diff, \is_string($option) ? $option : $default_array_override_option);
        }

        if ($is_base_sequential !== $is_diff_sequential) {
            return empty($diff) ? $base : $diff ;
        }

        foreach ($diff as $key => $value) {
            [$key, $apply_option] = OverrideOption::split($key);
            $apply_option         = $apply_option ?? $option[$key] ?? null ;
            if (isset($base[$key])) {
                $base[$key] = static::override($base[$key], $value, $apply_option, $default_array_override_option);
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
            case OverrideOption::APEND:
                foreach ($diff as $value) {
                    $base[] = $value;
                }
                return $base;
            case OverrideOption::REPLACE:
                return $diff;
        }

        throw new \LogicException("Invalid array merge mode '{$option}' given.");
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
            if ($values instanceof Collection) {
                $values = $values->all();
            } elseif (! is_array($values)) {
                continue;
            }
            $results = array_merge($results, $values);
        }
        return $results;
    }

    /**
     * Cross join the given arrays, returning all possible permutations.
     *
     * @param  array  ...$arrays
     * @return array
     */
    public static function crossJoin(...$arrays) : array
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
        return is_array($value) || $value instanceof \ArrayAccess;
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
     * @param  callable  $callback
     * @return array
     */
    public static function where(?array $array, callable $callback)
    {
        if ($array === null) {
            return null;
        }
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }
    
    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param  array|null  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public static function first($array, callable $callback = null, $default = null)
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
     * @param  int  $depth
     * @return array
     */
    public static function flatten(?array $array, $depth = INF) : ?array
    {
        if ($array === null) {
            return null;
        }
        $result = [];
        foreach ($array as $item) {
            $item = $item instanceof Collection ? $item->all() : $item;
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
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public static function last(?array $array, callable $callback = null, $default = null)
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
    public static function shuffle(?array $array, $seed = null) : ?array
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
     * @return int
     */
    public static function count($value) : int
    {
        if (is_array($value) || $value instanceof \Countable) {
            return count($value);
        }
        if (is_iterable($value)) {
            $count = 0;
            foreach ($value as $item) {
                $count++;
            }
            return $count;
        }
        return $value === null ? 0 : 1 ;
    }

    /**
     * Convert to array from Collection or Arrayable.
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
        if ($items instanceof Collection) {
            return $items->all();
        }
        if (method_exists($items, 'toArray')) {
            return $items->toArray();
        }
        if ($items instanceof \JsonSerializable) {
            if (is_array($json = $items->jsonSerialize())) {
                return $json;
            }
        }
        if ($items instanceof \Traversable) {
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
     * @param  callable  $callback function($value, $key) { ... }
     * @return array
     */
    public function map(callable $callback, array $array) : array
    {
        $keys = array_keys($array);
        return array_combine($keys, array_map($callback, $array, $keys));
    }
}
