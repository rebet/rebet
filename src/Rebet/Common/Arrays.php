<?php
namespace Rebet\Common;

/**
 * 配列関連 ユーティリティ クラス
 *
 * 配列制御に関連する簡便なユーティリティメソッドを集めたクラスです。
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Arrays
{
    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * 対象のリストから指定の件数だけランダムに選択します。
     *
     * ex)
     * [$winner, $loser] = Arrays::randomSelect($lottery_applicants, 3);
     *
     * @param array $list 選択対象リスト
     * @param int $select_count 選択数
     * @return array [ [選択された要素], [選択されなかった要素] ]
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
      * 指定の配列が index = 0 から始まる連番配列かチェックします。
      * ※null 指定時は false を返します
      *
      * ex)
      * Arrays::isSequential([]);                             //=> true
      * Arrays::isSequential([1,2,3]);                        //=> true
      * Arrays::isSequential([0 => 'a', '1' => 'b']);         //=> true
      * Arrays::isSequential([0 => 'a', 2 => 'c', 1 => 'b']); //=> false
      * Arrays::isSequential([1 => 'c', 2 => 'b']);           //=> false
      * Arrays::isSequential(['a' => 'a', 'b' => 'b']);       //=> false
      *
      * @param  array|null $array 配列
      * @return bool true : 連番配列／false : 連想配列 or 跳び番配列
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
    * 多次元配列を一次元配列に変換します。
    *
    * ex)
    * Arrays::flatten([1, 2, [3]]);         //=> [1, 2, 3]
    * Arrays::flatten([1, 2, [3, [4], 5]]); //=> [1, 2, 3, 4, 5]
    *
    * @param array|null $array 多次元配列
    * @return array|null 一次元配列
    */
    public static function flatten(?array $array) : ?array
    {
        return $array === null ? null : iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array)), false);
    }
    
    /**
     * Row要素（＝オブジェクトor配列）を含む配列(Table)から指定のプロパティ要素(Column)をキー及び値として連想配列を再構築します。
     * ※$*_field には .(dot) 区切り指定による階層アクセスが指定できます。
     *
     * ex)
     * $user_ids   = Arrays::remap($users, null, 'user_id');        //=> [21, 35, 43, ...]
     * $user_names = Arrays::remap($users, 'user_id', 'name');      //=> [21 => 'John', 35 => 'David', 43 => 'Linda', ...]
     * $user_banks = Arrays::remap($users, 'user_id', 'bank.name'); //=> [21 => 'City', 35 => 'JPMorgan', 43 => 'Montreal', ...]
     * $user_map   = Arrays::remap($users, 'user_id', null);        //=> [21 => <<Row object>>, 35 => <<Row object>>, 43 => <<Row object>>, ...]
     *
     * @param array|null $list オブジェクトが格納された配列
     * @param int|string|null $key_field 抽出データのキーとなるフィールド名/インデックス（blank 指定時は連番配列となる）
     * @param int|string|null $value_field 抽出データの値となるフィールド名/インデックス（blank 指定時はRow要素自体が対象となる）
     * @return array 列データ
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
     * ベースとなる連想配列に対して、差分の連想配列でマージ／上書します。
     *
     * 本メソッドは連想配列の値が連想配列である場合は再帰的にマージ／上書処理をされる点で array_merge と異なり、
     * 連想配列の値がオブジェクトの場合に値を上書きする点で array_merge_recursive と異なります。
     *
     * 具体的には以下のような挙動をします。
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
     * なお、この挙動は下記のオプション指定によって変更することができます。
     *
     * 　- 配列(Map or Array)の マージ挙動 は OverrideOption::REPLACE（='!'） によって置換挙動に変更できます。
     * 　- 配列(Array)       の マージ挙動 は OverrideOption::PREPEND（='<'） によって前方追加に変更できます。
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
     * なお、上記のコードは下記のように差分MAPのキー名末尾に '!' を付与することでも指定可能です。
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
     * @param mixed $base ベースデータ
     * @param mixed $diff 差分データ
     * @param array|string $option オプション
     * @param string $default_array_merge_mode デフォルト配列マージモード（デフォルト：OverrideOption::APEND）
     * @return マージ済みのデータ
     */
    public static function override($base, $diff, $option = [], string $default_array_merge_mode = OverrideOption::APEND)
    {
        if (!is_array($base) || !is_array($diff) || $option === OverrideOption::REPLACE) {
            return $diff;
        }

        $is_base_sequential = self::isSequential($base);
        $is_diff_sequential = self::isSequential($diff);
        if ($is_base_sequential && $is_diff_sequential) {
            return static::arrayMerge($base, $diff, \is_string($option) ? $option : $default_array_merge_mode);
        }

        if ($is_base_sequential !== $is_diff_sequential) {
            return $diff;
        }

        foreach ($diff as $key => $value) {
            [$key, $apply_option] = OverrideOption::split($key);
            $apply_option = $apply_option ?? $option[$key] ?? null ;
            if (isset($base[$key])) {
                $base[$key] = static::override($base[$key], $value, $apply_option, $default_array_merge_mode);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
    
    /**
     * オプションの内容にしたがって連番配列をマージします。
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
