<?php
namespace Rebet\Common;

use Rebet\Common\StringUtil;

/**
 * 汎用 ユーティリティ クラス
 * 
 * 各種特化ユーティリティに分類されない簡便なユーティリティメソッドを集めたクラスです。
 * 本クラスに定義されているメソッドは将来的に特化クラスなどへ移設される可能性があります。
 * 
 * $user_name = Util::get($_REQUEST, 'user.name');
 * if(Util::isBlank($user_name)) {
 *     // something to do
 * }
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Util {
	
	/**
	 * インスタンス化禁止
	 */
	private function __construct() {}

	/**
	 * 三項演算のメソッド版
	 * 
	 * ex)
	 * Util::when(1 === 1, 'yes', 'no'); //=> 'yes'
	 * 
	 * @param ?mixed $expr 判別式
	 * @param ?mixed $ifTrue 真の場合の値
	 * @param ?mixed $ifFalse 偽の場合の値
	 * @return ?mixed 三項演算の結果
	 */
	public static function when($expr, $ifTrue, $ifFalse) {
		return $expr ? $ifTrue : $ifFalse ;
	}
	
	/**
	 * 空でない最初の要素を返します。
	 * 
	 * ex)
	 * Util::coalesce(null, [], '', 0, 3, 'a'); //=> 3
	 * 
	 * @param ?mixed $items 要素
	 * @return ?mixed 空でない最初の要素
	 */
	public static function coalesce(...$items) {
		foreach ($items as $item) {
			if(!empty($item)) { return $item; }
		}
		return null;
	}
	
	/**
	 * 配列又はオブジェクトから値を取得します。
	 * 
	 * ex)
	 * Util::get($user, 'name');
	 * Util::get($user, 'bank.name');
	 * Util::get($user, 'shipping_address.0', $user->address);
	 * Util::get($_REQUEST, 'opt_in', false);
	 * 
	 * @param  array|obj $obj 配列 or オブジェクト
	 * @param  int|sting $key キー名(.[dot]区切りでオブジェクトプロパティ階層指定可)
	 * @param  mixed $default デフォルト値
	 * @return mixed 値
	 */
	public static function get($obj, $key, $default = null) {
		if($obj === null) { return $default; }
		
		$current = StringUtil::latrim($key, '.');
		if($current != $key) {
			$target = self::get($obj, $current);
			if($target === null) { return $default; }
			return self::get($target, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $default);
		}

		if(is_array($obj)) {
			if(!isset($obj[$current])) { return $default; }
			return self::nvl($obj[$current], $default);
		}

		if(!property_exists($obj, $current)) { return $default; }
		return self::nvl($obj->$current, $default);
	}
	
	/**
	 * 配列又はオブジェクトに値を設定します。
	 * 
	 * ex)
	 * Util::set($user, 'name', 'new name');
	 * Util::set($user, 'bank.name', 'new bank');
	 * Util::set($user, 'shipping_address.0', $user->address);
	 * Util::set($_REQUEST, 'opt_in', false);
	 * 
	 * @param  array|obj $obj 配列 or オブジェクト
	 * @param  int|sting $key キー名(.[dot]区切りでオブジェクトプロパティ階層指定可)
	 * @param  mixed $value 設定値
	 * @return mixed 値
	 * @throw \OutOfBoundsException
	 */
	public static function set(&$obj, $key, $value) : void {
		$current = StringUtil::latrim($key, '.');
		if(is_array($obj)) {
			if(!\array_key_exists($current, $obj)){
				throw new \OutOfBoundsException("Nested terminate key {$current} does not exist.");
			}
			if($current != $key) {
				self::set($obj[$current], \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $value);
			} else {
				$obj[$current] = $value;
			}
			return;
		}

		if(!\property_exists($obj, $current)){
			throw new \OutOfBoundsException("Nested terminate key {$current} does not exist.");
		}
		if($current != $key) {
			self::set($obj->$current, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1), $value);
		} else {
			$obj->$current = $value;
		}
		return;
	}
	
	/**
	 * 配列又はオブジェクトが指定プロパティを持つかチェックします。
	 * 
	 * ex)
	 * Util::has($user, 'name');
	 * Util::has($user, 'bank.name');
	 * Util::has($user, 'shipping_address.0');
	 * Util::has($_REQUEST, 'opt_in');
	 * 
	 * @param  array|obj $obj 配列 or オブジェクト
	 * @param  int|sting $key キー名(.[dot]区切りでオブジェクトプロパティ階層指定可)
	 * @return true: 存在する, false: 存在しない
	 */
	public static function has($obj, $key) {
		if($obj === null) { return false; }
		
		$current  = StringUtil::latrim($key, '.');
		$nest_obj = null;
		if(is_array($obj)){
			if(!array_key_exists($current, $obj)) { return false; }
			$nest_obj = $obj[$current];
		} else {
			if(!property_exists($obj, $current)) { return false; }
			$nest_obj = $obj->{$current};
		}

		return $current == $key ? true : self::has($nest_obj, \mb_substr($key, \mb_strlen($current) - \mb_strlen($key) + 1));
	}

	/**
	 * 対象の値が null か判定します。
	 * 
	 * ex)
	 * Util::isNull(null   ); //=> true
	 * Util::isNull(false  ); //=> false
	 * Util::isNull('false'); //=> false
	 * Util::isNull(0      ); //=> false
	 * Util::isNull('0'    ); //=> false
	 * Util::isNull(''     ); //=> false
	 * Util::isNull([]     ); //=> false
	 * Util::isNull([null] ); //=> false
	 * Util::isNull([1]    ); //=> false
	 * Util::isNull('abc'  ); //=> false
	 * 
	 * @param  ?mixed $value 値
	 * @return bool treu: null, false: null以外
	 */
	public static function isNull($value) : bool {
		return $value === null ;
	}

	/**
	 * 対象の値が null の場合にデフォルト値を返します。
	 * 
	 * @param  ?mixed $value 値
	 * @param  ?mixed $default デフォルト値
	 * @return ?mixed 値
	 * @see self::isNull()
	 */
	public static function nvl($value, $default) {
		return self::isNull($value) ? $default : $value ;
	}
	
	/**
	 * 対象の値が blank か判定します。
	 * 
	 * ex)
	 * Util::isNull(null   ); //=> true
	 * Util::isNull(false  ); //=> false
	 * Util::isNull('false'); //=> false
	 * Util::isNull(0      ); //=> false
	 * Util::isNull('0'    ); //=> false
	 * Util::isNull(''     ); //=> true
	 * Util::isNull([]     ); //=> true
	 * Util::isNull([null] ); //=> false
	 * Util::isNull([1]    ); //=> false
	 * Util::isNull('abc'  ); //=> false
	 * 
	 * @param  ?mixed $value 値
	 * @return bool treu: blank, false: blank以外
	 */
	public static function isBlank($value) : bool {
		return $value === null || $value === '' || $value === [] ;
	}
	
	/**
	 * 対象の値が blank の場合にデフォルト値を返します。
	 * 
	 * @param  ?mixed $value 値
	 * @param  ?mixed $default デフォルト値
	 * @return ?mixed 値
	 * @see self::isBlank()
	 */
	public static function bvl($value, $default) {
		return self::isBlank($value) ? $default : $value ;
	}
	
	/**
	 * 対象の値が empty か判定します。
	 * 
	 * ex)
	 * Util::isNull(null   ); //=> true
	 * Util::isNull(false  ); //=> false
	 * Util::isNull('false'); //=> false
	 * Util::isNull(0      ); //=> true
	 * Util::isNull('0'    ); //=> false
	 * Util::isNull(''     ); //=> true
	 * Util::isNull([]     ); //=> true
	 * Util::isNull([null] ); //=> false
	 * Util::isNull([1]    ); //=> false
	 * Util::isNull('abc'  ); //=> false
	 * 
	 * @param  ?mixed $value 値
	 * @return bool treu: empty, false: empty以外
	 */
	public static function isEmpty($value) : bool {
		return $value === null || $value === '' || $value === [] || $value === 0 ;
	}
	
	/**
	 * 対象の値が empty の場合にデフォルト値を返します。
	 * 
	 * @param  ?mixed $value 値
	 * @param  ?mixed $default デフォルト値
	 * @return ?mixed 値
	 * @see self::isEmpty()
	 */
	public static function evl($value, $default) {
		return self::isEmpty($value) ? $default : $value ;
	}
	
	/**
	 * ヒアドキュメントへの文字列埋め込み用の匿名関数を返します。
	 * 
	 * ex)
	 * $_ = Util::heredocImplanter();
	 * $str = <<<EOS
	 *     text text text {$_(Class::CONST)}
	 *     {$_(CONSTANT)} text
	 * EOS;
	 * 
	 * @return function
	 */
	public static function heredocImplanter() : callable {
		return function($s){ return $s; };
	}

	/**
	 * int 型に変換します
	 * ※ null/空文字 は null が返ります
	 * 
	 * @param number|string|null $var 変換対象
	 * @param ?int $base 基数
	 * @return ?int 変換した値
	 */
	public static function intval($var, int $base = null) : ?int {
		return $var === null || $var === '' ? null : intval($var, $base);
	}
	
	/**
	 * float 型に変換します
	 * ※ null/空文字 は null が返ります
	 * 
	 * @param number|string|null $var 変換対象
	 * @return ?float 変換した値
	 */
	public static function floatval($var) : ?float {
		return $var === null || $var === '' ? null : floatval($var);
	}
}