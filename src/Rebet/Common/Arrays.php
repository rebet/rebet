<?php
namespace Rebet\Common;

/**
 * Array Utility Class
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
     * It randomly selects the specified number of items from the target list.
     *
     * ex)
     * [$winner, $loser] = Arrays::randomSelect($lottery_applicants, 3);
     *
     * @param array $list
     * @param int $select_count
     * @return array [[selected_item, ...], [not_selected_items, ...]]
     */
    public static function randomSelect(array $list, int $select_count) : array
    {
        if (count($list) <= $select_count) {
            return [$list, []] ;
        }
        
        $selected   = [];
        $max_idx    = count($list) - 1;
        $i          = 0;
        $missselect = 0;
        shuffle($list);
        while ($i < $select_count) {
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

        while ($i < $select_count) {
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
    public static function isSequential(?array $array) : bool
    {
        if ($array === null) {
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
     * Convert a multidimensional array to a one-dimensional array.
     *
     * ex)
     * Arrays::flatten([1, 2, [3]]);         //=> [1, 2, 3]
     * Arrays::flatten([1, 2, [3, [4], 5]]); //=> [1, 2, 3, 4, 5]
     *
     * @param array|null $array
     * @return array|null
     */
    public static function flatten(?array $array) : ?array
    {
        return $array === null ? null : iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array)), false);
    }

    /**
     * Reassemble a map of given columns (as key or value) from an array containing object or array.
     * # You can use dot access of Reflector::get() in $key_field and $value_field.
     *
     * ex)
     * $user_ids   = Arrays::remap($users, null     , 'user_id'  ); //=> [21, 35, 43, ...]
     * $user_names = Arrays::remap($users, 'user_id', 'name'     ); //=> [21 => 'John', 35 => 'David', 43 => 'Linda', ...]
     * $user_banks = Arrays::remap($users, 'user_id', 'bank.name'); //=> [21 => 'City', 35 => 'JPMorgan', 43 => 'Montreal', ...]
     * $user_map   = Arrays::remap($users, 'user_id', null       ); //=> [21 => <User object>, 35 => <User object>, 43 => <User object>, ...]
     *
     * @param array|null $list
     * @param int|string|null $key_field Field name / index as key of extracted data (It becomes serial number array when blank is specified)
     * @param int|string|null $value_field Field name / index as the value of extracted data (Row element itself is targeted when blank is specified)
     * @return array 
     * @see Reflector::get()
     */
    public static function remap(?array $list, $key_field, $value_field) : array
    {
        if (Utils::isEmpty($list)) {
            return [];
        }
        $remaps = [];
        foreach ($list as $i => $row) {
            $remaps[Utils::isBlank($key_field) ? $i : Reflector::get($row, $key_field)] = Utils::isBlank($value_field) ? $row : Reflector::get($row, $value_field);
        }
        return $remaps;
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
        if (!is_array($base) || !is_array($diff) || $option === OverrideOption::REPLACE) {
            return $diff;
        }

        $is_base_sequential = self::isSequential($base);
        $is_diff_sequential = self::isSequential($diff);
        if ($is_base_sequential && $is_diff_sequential) {
            return static::arrayMerge($base, $diff, \is_string($option) ? $option : $default_array_override_option);
        }

        if ($is_base_sequential !== $is_diff_sequential) {
            return $diff;
        }

        foreach ($diff as $key => $value) {
            [$key, $apply_option] = OverrideOption::split($key);
            $apply_option = $apply_option ?? $option[$key] ?? null ;
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
     * @param array $base
     * @param array $diff
     * @param string $option
     * @return array
     */
    private static function arrayMerge(array $base, array $diff, string $option) : array
    {
        switch ($option) {
            case OverrideOption::PREPEND:
                return \array_merge($diff, $base);
            case OverrideOption::APEND:
                return \array_merge($base, $diff);
            case OverrideOption::REPLACE:
                return $diff;
        }

        throw new \LogicException("Invalid array merge mode '{$option}' given.");
    }
}
