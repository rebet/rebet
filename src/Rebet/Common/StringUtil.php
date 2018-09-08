<?php
namespace Rebet\Common;

/**
 * 文字列関連 ユーティリティ クラス
 * 
 * 文字列に関連した簡便なユーティリティメソッドを集めたクラスです。
 * 
 * if(StringUtil::endWith($file, '.pdf')) {
 *     // Something to do
 * }
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class StringUtil {
	
	/**
	 * インスタンス化禁止
	 */
	private function __construct() {}

	/**
	 * 最も左側にある指定文字列より左側(Left Before)の文字をトリムします。
	 * 
	 * ex)
	 * StringUtil::lbtrim('1.2.3', '.');        //=> '2.3'
	 * StringUtil::lbtrim('1.2.3', '.', false); //=> '.2.3'
	 * 
	 * @param string|null $str トリム対象
	 * @param string $delimiter 区切り文字
	 * @param bool $remove_delimiter 区切り文字削除／true : 削除する, false : 削除しない （デフォルト：true）
	 * @return string|null トリム文字列
	 */
	public static function lbtrim(?string $str, string $delimiter, bool $remove_delimiter = true) : ?string {
		$start = strpos($str, $delimiter);
		if($start === false) { return $str; }
		return mb_substr($str, $start + ($remove_delimiter ? mb_strlen($delimiter) : 0));
	}
	
	/**
	 * 最も左側にある指定文字列より右側(Left After)の文字をトリムします。
	 * 
	 * ex)
	 * StringUtil::latrim('1.2.3', '.');        //=> '1'
	 * StringUtil::latrim('1.2.3', '.', false); //=> '1.'
	 * 
	 * @param string|null $str トリム対象
	 * @param string $delimiter 区切り文字
	 * @param bool $remove_delimiter 区切り文字削除／true : 削除する, false : 削除しない （デフォルト：true）
	 * @return string|null トリム文字列
	 */
	public static function latrim(?string $str, string $delimiter, bool $remove_delimiter = true) : ?string {
		$end = strpos($str, $delimiter);
		if($end === false) { return $str; }
		return mb_substr($str, 0, $end + ($remove_delimiter ? 0 : mb_strlen($delimiter)));
	}
	
	/**
	 * 最も右側にある指定文字列より左側(Right Before)の文字をトリムします。
	 * 
	 * ex)
	 * StringUtil::rbtrim('1.2.3', '.');        //=> '3'
	 * StringUtil::rbtrim('1.2.3', '.', false); //=> '.3'
	 * 
	 * @param string|null $str トリム対象
	 * @param string $delimiter 区切り文字
	 * @param bool $remove_delimiter 区切り文字削除／true : 削除する, false : 削除しない （デフォルト：true）
	 * @return string|null トリム文字列
	 */
	public static function rbtrim(?string $str, string $delimiter, bool $remove_delimiter = true) : ?string {
		$start = strrpos($str, $delimiter);
		if($start === false) { return $str; }
		return mb_substr($str, $start + ($remove_delimiter ? mb_strlen($delimiter) : 0));
	}
	
	/**
	 * 最も右側にある指定文字列より右側(Right After)の文字をトリムします。
	 * 
	 * ex)
	 * StringUtil::ratrim('1.2.3', '.');        //=> '1.2'
	 * StringUtil::ratrim('1.2.3', '.', false); //=> '1.2.'
	 * 
	 * @param string|null $str トリム対象
	 * @param string $delimiter 区切り文字
	 * @param bool $remove_delimiter 区切り文字削除／true : 削除する, false : 削除しない （デフォルト：true）
	 * @return string|null トリム文字列
	 */
	public static function ratrim(?string $str, string $delimiter, bool $remove_delimiter = true) : ?string {
		$end = strrpos($str, $delimiter);
		if($end === false) { return $str; }
		return mb_substr($str, 0, $end + ($remove_delimiter ? 0 : mb_strlen($delimiter)));
	}
	
	/**
	 * 左端の指定文字列の繰り返しをトリムします。
	 * 
	 * ex)
	 * StringUtil::ltrim('   abc   ');            //=> 'abc   '
	 * StringUtil::ltrim('111abc111', '1');       //=> 'abc111'
	 * StringUtil::ltrim('12121abc21212', '12');  //=> '1abc21212'
	 * StringUtil::ltrim('　　　全角　　　', '　'); //=> '全角　　　'
	 * 
	 * @param string|null $str トリム対象
	 * @param string $prefix トリム文字列
	 * @return string|null トリム文字列
	 */
	public static function ltrim(?string  $str, string $prefix = ' ') : ?string {
		return $str === null ? null : preg_replace("/^(".preg_quote($prefix).")*/u", '', $str);
	}
	
	/**
	 * 右端の指定文字列の繰り返しをトリムします。
	 * 
	 * ex)
	 * StringUtil::rtrim('   abc   ');            //=> '   abc'
	 * StringUtil::rtrim('111abc111', '1');       //=> '111abc'
	 * StringUtil::rtrim('12121abc21212', '12');  //=> '12121abc2'
	 * StringUtil::rtrim('　　　全角　　　', '　'); //=> '　　　全角'
	 * 
	 * @param string|null $str トリム対象
	 * @param string $suffix トリム文字列
	 * @return string|null トリム文字列
	 */
	public static function rtrim(?string $str, string $suffix = ' ') : ?string {
		return $str === null ? null : preg_replace("/(".preg_quote($suffix).")*$/u", '', $str);
	}
	
	/**
	 * 指定の文字列 [$haystack] が指定の文字列 [$needle] で始まるか検査します。
	 * 
	 * ex)
	 * StringUtil::startWith('abc123', 'abc'); //=> true
	 * 
	 * @param string|null $haystack 検査対象文字列
	 * @param string $needle   被検査文字列
	 * @return bool true : 始まる／false : 始まらない
	 */
	public static function startWith(?string $haystack, string $needle) : bool {
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
	}
	
	/**
	 * 指定の文字列 [$haystack] が指定の文字列 [$needle] で終わるか検査します。
	 * 
	 * ex)
	 * StringUtil::endWith('abc123', '123'); //=> true
	 * 
	 * @param string|null $haystack 検査対象文字列
	 * @param string $needle 被検査文字列
	 * @return bool true : 終わる／false : 終わらない
	 */
	public static function endWith(?string $haystack, string $needle) : bool {
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
	}
	
	/**
	 * 機種依存文字が含まれるかチェックします。
	 * 
	 * ex)
	 * StringUtil::checkDependenceChar('あ①♬㈱♥');                //=> [2 => '♬', 4 => '♥']
	 * StringUtil::checkDependenceChar('あ①♬㈱♥', 'iso-2022-jp'); //=> [1 => '①', 2 => '♬', 3 => '㈱', 4 => '♥']
	 * StringUtil::checkDependenceChar('あ①♬㈱♥', 'UTF-8');       //=> []
	 * 
	 * @param string|null $text 検査対象文字列
	 * @param string $encode 機種依存チェックを行う文字コード（デフォルト： sjis-win）
	 * @return array 機種依存文字の配列
	 */
	public static function checkDependenceChar(?string $text, string $encode = 'sjis-win') : array {
		$org  = $text;
		$conv = mb_convert_encoding(mb_convert_encoding($text, $encode, 'UTF-8'), 'UTF-8', $encode);
		if(strlen($org) != strlen($conv)) {
			return array_diff(self::toCharArray($org), self::toCharArray($conv));
		}
		
		return [];
	}
	
	/**
	 * 文字列を文字の配列に変換します。
	 * 
	 * ex)
	 * StringUtil::toCharArray('abc'); //=> ['a', 'b', 'c']
	 * 
	 * @param string|null $string 文字列
	 * @return array 文字の配列
	 */
	public static function toCharArray (?string $string) : array {
		return preg_split("//u", $string, -1, PREG_SPLIT_NO_EMPTY);
	}
	
	/**
	 * スネークケース(snake_case)文字列をキャメルケース(CamelCase)文字列に変換します。
	 * 
	 * ex)
	 * StringUtil::camelize('snake_case'); //=> 'SnakeCase'
	 * 
	 * @param string|null $str スネークケース文字列
	 * @return string|null キャメルケース文字列
	 */
	public static function camelize(?string $str) : ?string {
		return $str === null ? null : str_replace('_', '', ucwords($str, '_'));
	}
	
	/**
	 * キャメルケース(CamelCase) 文字列をスネークケース(snake_case)文字列に変換します。
	 * 
	 * ex)
	 * StringUtil::camelize('CamelCase'); //=> 'camel_case'
	 * 
	 * @param  string|null $str キャメルケース文字列
	 * @return string|null スネークケース文字列
	 */
	public static function snakize(?string $str) : ?string {
		return $str === null ? null : strtolower(preg_replace('/[a-z]+(?=[A-Z])|[A-Z]+(?=[A-Z][a-z])/', '\0_', $str));
	}
	
	/**
	 * 指定文字の先頭を大文字にします。
	 * 
	 * ex)
	 * StringUtil::capitalize('snake_case'); //=> 'Snake_case'
	 * 
	 * @param string|null $str 文字列
	 * @return string|null 文字列
	 */
	public static function capitalize(?string $str) : ?string {
		return $str === null ? null : ucfirst($str);
	}
	
	/**
	 * 指定文字の先頭を小文字にします。
	 * 
	 * ex)
	 * StringUtil::capitalize('CamelCase'); //=> 'camelCase'
	 * 
	 * @param string|null $str 文字列
	 * @return string|null 文字列
	 */
	public static function uncapitalize(?string $str) : ?string {
		return $str === null ? null : lcfirst($str);
	}
}