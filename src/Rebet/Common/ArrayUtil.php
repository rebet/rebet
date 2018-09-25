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
class ArrayUtil
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
     * [$winner, $loser] = ArrayUtil::randomSelect($lottery_applicants, 3);
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
      * ArrayUtil::isSequential([]);                             //=> true
      * ArrayUtil::isSequential([1,2,3]);                        //=> true
      * ArrayUtil::isSequential([0 => 'a', '1' => 'b']);         //=> true
      * ArrayUtil::isSequential([0 => 'a', 2 => 'c', 1 => 'b']); //=> false
      * ArrayUtil::isSequential([1 => 'c', 2 => 'b']);           //=> false
      * ArrayUtil::isSequential(['a' => 'a', 'b' => 'b']);       //=> false
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
    * ArrayUtil::flatten([1, 2, [3]]);         //=> [1, 2, 3]
    * ArrayUtil::flatten([1, 2, [3, [4], 5]]); //=> [1, 2, 3, 4, 5]
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
     * $user_ids   = ArrayUtil::remap($users, null, 'user_id');        //=> [21, 35, 43, ...]
     * $user_names = ArrayUtil::remap($users, 'user_id', 'name');      //=> [21 => 'John', 35 => 'David', 43 => 'Linda', ...]
     * $user_banks = ArrayUtil::remap($users, 'user_id', 'bank.name'); //=> [21 => 'City', 35 => 'JPMorgan', 43 => 'Montreal', ...]
     * $user_map   = ArrayUtil::remap($users, 'user_id', null);        //=> [21 => <<Row object>>, 35 => <<Row object>>, 43 => <<Row object>>, ...]
     *
     * @param array|null $list オブジェクトが格納された配列
     * @param int|string|null $key_field 抽出データのキーとなるフィールド名/インデックス（null 指定時は連番配列となる）
     * @param int|string|null $value_field 抽出データの値となるフィールド名/インデックス（null 指定時はRow要素自体が対象となる）
     * @return array 列データ
     * @see Util::get()
     */
    public static function remap(?array $list, $key_field, $value_field) : array
    {
        if (Util::isEmpty($list)) {
            return [];
        }
        $remaps = [];
        foreach ($list as $i => $row) {
            $key = Util::get($row, $key_field);
            $remaps[$key ? $key : $i] = Util::isBlank($value_field) ? $row : Util::get($row, $value_field);
        }
        return $remaps;
    }

    /**
     * ベースとなる連想配列に対して、差分の連想配列でマージ／上書します。
     *
     * 本メソッドは連想配列の値が連想配列である場合は再帰的にマージ／上書処理をされる点で array_merge と異なり、
     * 連想配列の値がシーケンシャル配列又はオブジェクトの場合に値を上書きする点で array_merge_recursive と異なります。
     *
     * 具体的には以下のような挙動をします。
     *
     * ArrayUtil::override(
     *     [
     *         'map'   => ['a' => 'a', 'b' => 'b'],
     *         'array' => ['a', 'b'],
     *         'other' => 'a',
     *     ],
     *     [
     *         'map'   => ['a' => 'A', 'c' => 'C'],
     *         'array' => ['c'],
     *         'other' => 'A',
     *         'added' => 'added',
     *     ],
     * );
     * => [
     *     'map'   => ['a' => 'A', 'b' => 'b', 'c' => 'C'],
     *     'array' => ['c'],
     *     'other' => 'A',
     *     'added' => 'added',
     * ]
     *
     * なお、この挙動は下記の2種のオプション指定によって変更することができます。
     *
     * 　- Map(連想配列)   の マージ挙動 は差分MAPのキー名末尾に '!' を付与することで、上書挙動に変更できます。
     * 　- Array(連番配列) の 上書き挙動 は差分MAPのキー名末尾に '+' を付与することで、マージ（アペンド）挙動に変更できます。
     *
     * ArrayUtil::override(
     *     [
     *         'map'    => ['a' => 'a', 'b' => 'b'],
     *         'array'  => ['a', 'b'],
     *     ],
     *     [
     *         'map!'   => ['a' => 'A', 'c' => 'C'],
     *         'array+' => ['c'],
     *     ],
     * );
     * => [
     *     'map'   => ['a' => 'A', 'c' => 'C'],
     *     'array' => ['a', 'b', 'c'],
     * ]
     *
     * なお、上記のコードは下記のオプション指定と同義です。
     *
     * ArrayUtil::override(
     *     [
     *         'map'   => ['a' => 'a', 'b' => 'b'],
     *         'array' => ['a', 'b'],
     *     ],
     *     [
     *         'map'   => ['a' => 'A', 'c' => 'C'],
     *         'array' => ['c'],
     *     ],
     *     [
     *         'map'   => OverrideOption::MAP_NO_MERGE, //= '!'
     *         'array' => OverrideOption::ARRAY_APPEND, //= '+'
     *     ],
     * );
     *
     * @param mixed $base ベースデータ
     * @param mixed $diff 差分データ
     * @param array $option オプション
     * @return マージ済みのデータ
     */
    public static function override($base, $diff, $option = [])
    {
        if (!is_array($base) || !is_array($diff) || self::isSequential($base) || self::isSequential($diff)) {
            return $diff;
        }

        foreach ($diff as $key => $value) {
            if (isset($base[$key])) {
                $base[$key] = self::override($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
