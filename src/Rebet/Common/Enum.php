<?php
namespace Rebet\Common;

/**
 * 列挙 クラス
 *
 * 【使い方】
 * // 基本系
 * class Gender extends Enum {
 *     const MALE   = [1, '男性'];
 *     const FEMALE = [2, '女性'];
 * }
 *
 * => Gender::MALE() // Access Enum Object
 *
 * // 定数不要系
 * class Gender extends Enum {
 *     protected static function generate() {
 *         return [
 *             new static('M', '男性'),
 *             new static('F', '女性'),
 *         ];
 *     }
 * }
 *
 * // メソッド拡張系
 * class Gender extends Enum {
 *     const MALE   = [1, '男性'];
 *     const FEMALE = [2, '女性'];
 *
 *     public function isMale()   { return $this->value === 1; }
 *     public function isFemale() { return $this->value === 2; }
 * }
 *
 * // フィールド拡張系
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
 * // 匿名クラス拡張系
 * abstract class JobOfferCsvFormat extends Enum {
 *     public abstract function convert(array $row) : UserForm ;
 *
 *     protected static function generate() {
 *         return [
 *              new class(1, '求人サイトA') extends JobOfferCsvFormat {
 *                  public function convert(array $row) : UserForm {
 *                      $form = new UserForm();
 *                      (snip)
 *                      $form->name = "{$row[0]} {$row[1]}"; // combine first name and last name column.
 *                      (snip)
 *                      return $form;
 *                  }
 *              },
 *              new class(2, '求人サイトB') extends JobOfferCsvFormat {
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
 * // DBマスタ参照系
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
    /**
     * 列挙データキャッシュ
     *
     * self::$enum_data_cache = [
     *     EnumClassName => [
     *         ConstName => EnumObject,
     *     ],
     * ]
     */
    private static $enum_data_cache = [];
    
    /**
     * 列挙リストキャッシュ
     *
     * self::$enum_list_cache = [
     *     EnumClassName => [EnumObject, ... ],
     * ]
     */
    private static $enum_list_cache = [];
    
    /**
     * 列挙マップキャッシュ
     *
     * self::$enum_map_cache = [
     *     EnumClassName@FieldName => [
     *         FieldValue => EnumObject,
     *     ],
     * ]
     */
    private static $enum_map_cache  = [];

    /**
     * 値
     * @var mixed
     */
    public $value;
    
    /**
     * ラベル
     * @var string
     */
    public $label;
    
    /**
     * 列挙生成
     *
     * @param mixed 値
     * @param string $label ラベル
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
     * 列挙の値を検証します。
     * @param mixed 比較値
     * @return bool true: 一致 / false: 不一致
     */
    public function equals($value) : bool
    {
        return $value instanceof static ? $this === $value : $this->value == $value ;
    }
    
    /**
     * 列挙が指定の配列内に含まれるか検証します。
     * @param mixed ...$values
     * @return boolean true: 含まれる / false: 含まれない
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
     * 列挙を文字列します。
     * @return string
     */
    public function __toString() : string
    {
        return $this->label;
    }
    
    /**
     * 列挙を JSON Serialize します。
     */
    public function jsonSerialize()
    {
        return $this->value;
    }

    /**
     * 列挙型の const 定義から列挙オブジェクトを生成します。
     *
     * @param string $name const 定数名
     */
    private static function constToEnum(\ReflectionClass $rc, $name) : ?Enum
    {
        $clazz = $rc->getName();
        if (isset(self::$enum_data_cache[$clazz][$name])) {
            return self::$enum_data_cache[$clazz][$name];
        }
        if (!defined("static::{$name}")) {
            throw new \LogicException("Invalid enum const. {$clazz}::{$name} is not defined.");
        }
        $args = $rc->getConstant($name);
        $enum = new static(...$args);
        self::$enum_data_cache[$clazz][$name] = $enum;
        return $enum;
    }

    /**
     * 静的メソッド呼び出しによる列挙オブジェクトアクセスを提供します。
     */
    public static function __callStatic($name, array $args)
    {
        return self::constToEnum(new \ReflectionClass(get_called_class()), $name);
    }

    /**
     * 列挙の一覧を生成します。
     * @return array 列挙の一覧
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
     * 列挙定数の一覧 array(Enum) を取得します。
     * ※列挙クラス名単位で generate された列挙一覧をキャッシュし、再利用します。
     */
    public static function lists() : array
    {
        $clazz = get_called_class();
        if (isset(self::$enum_list_cache[$clazz])) {
            return self::$enum_list_cache[$clazz];
        }
        self::$enum_list_cache[$clazz] = static::generate();
        return self::$enum_list_cache[$clazz];
    }
    
    /**
     * $enum->$field ⇒ $enum の連想配列を取得します。
     * ※同じ値を持つ列挙が存在する場合、 enum::lists() の順序で後勝ちとなります
     *
     * @throws \LogicException
     */
    public static function maps($field = 'value') : array
    {
        $clazz = get_called_class();
        if (!\property_exists($clazz, $field)) {
            throw new \LogicException("Invalid property access. Property {$clazz}->{$field} is not exists.");
        }

        $key = "{$clazz}@{$field}";
        if (isset(self::$enum_map_cache[$key])) {
            return self::$enum_map_cache[$key];
        }
        
        $maps = [];
        foreach (self::lists() as $enum) {
            $maps[$enum->$field] = $enum;
        }
        self::$enum_map_cache[$key] = $maps;
        
        return $maps;
    }
    
    /**
     * 指定フィールドの値を持つ列挙を取得します。
     * ※同じ値を持つ列挙が存在する場合、 Enum::lists() の順序で後勝ちとなります
     */
    public static function fieldOf(string $field, $value) : ?Enum
    {
        if ($value instanceof static) {
            return $value;
        }
        $maps = self::maps($field);
        return isset($maps[$value]) ? $maps[$value] : null ;
    }
    
    /**
     * 対象の値を持つ列挙を取得します。
     * ※同じ値を持つ列挙が存在する場合、 Enum::lists() の順序で後勝ちとなります
     */
    public static function valueOf($value) : ?Enum
    {
        return self::fieldOf('value', $value);
    }
    
    /**
     * 対象のラベルを持つ列挙を取得します。
     * ※同じ値を持つ列挙が存在する場合、 Enum::lists() の順序で後勝ちとなります
     */
    public static function labelOf(string $label) : ?Enum
    {
        return self::fieldOf('label', $label);
    }
    
    /**
     * 指定フィールドの一覧を配列で取得します。
     * @param string $name
     */
    public static function listOf(string $name, \Closure $matcher = null) : array
    {
        $clazz = get_called_class();
        if (!\property_exists($clazz, $name)) {
            throw new \LogicException("Invalid property access. Property {$clazz}->{$name} is not exists.");
        }

        $values = [];
        foreach (self::lists() as $enum) {
            if ($matcher == null || $matcher($enum)) {
                $values[] = $enum->$name;
            }
        }
        return $values;
    }
    
    /**
     * 値の一覧を配列で取得します。
     */
    public static function values(\Closure $matcher = null) : array
    {
        return self::listOf('value', $matcher);
    }
    
    /**
     * ラベルの一覧を配列で取得します。
     */
    public static function labels(\Closure $matcher = null) : array
    {
        return self::listOf('label', $matcher);
    }
    
    /**
     * 簡易ワークフロー
     * 指定の状況(context)に応じたある列挙値(current)から遷移可能な次の列挙一覧を取得します。
     * 必要に応じてサブクラスでオーバーライドして下さい。
     *
     * @param type $current
     * @param array|null $context
     */
    public static function nexts($current, ?array $context = null) : array
    {
        return self::lists();
    }
    
    /**
     * 簡易ワークフロー
     * 指定フィールドの一覧を配列で取得します。
     *
     * @param string $name
     */
    public static function nextOf(string $name, $current, ?array $context = null) : array
    {
        $clazz = get_called_class();
        if (!\property_exists($clazz, $name)) {
            throw new \LogicException("Invalid property access. Property {$clazz}->{$name} is not exists.");
        }

        $values = [];
        foreach (static::nexts($current, $context) as $enum) {
            $values[] = $enum->$name;
        }
        return $values;
    }
    
    /**
     * 簡易ワークフロー
     * 値の一覧を配列で取得します。
     */
    public static function nextValues($current, ?array $context = null) : array
    {
        return self::nextOf('value', $current, $context);
    }
    
    /**
     * 簡易ワークフロー
     * ラベルの一覧を配列で取得します。
     */
    public static function nextLabels($current, ?array $context = null) : array
    {
        return self::nextOf('label', $current, $context);
    }
}
