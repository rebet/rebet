<?php
namespace Rebet\Common;

/**
 * 文字列関連 ユーティリティ クラス
 *
 * 文字列に関連した簡便なユーティリティメソッドを集めたクラスです。
 *
 * if(Strings::endsWith($file, '.pdf')) {
 *     // Something to do
 * }
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Strings
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * 最も左側にある指定文字列より左側(Left Before)の文字をトリムします。
     *
     * ex)
     * Strings::lbtrim('1.2.3', '.');        //=> '2.3'
     * Strings::lbtrim('1.2.3', '.', false); //=> '.2.3'
     *
     * @param string|null $str トリム対象
     * @param string $delimiter 区切り文字
     * @param bool $remove_delimiter 区切り文字削除／true : 削除する, false : 削除しない （デフォルト：true）
     * @return string|null トリム文字列
     */
    public static function lbtrim(?string $str, string $delimiter, bool $remove_delimiter = true) : ?string
    {
        $start = strpos($str, $delimiter);
        if ($start === false) {
            return $str;
        }
        return mb_substr($str, $start + ($remove_delimiter ? mb_strlen($delimiter) : 0));
    }
    
    /**
     * 最も左側にある指定文字列より右側(Left After)の文字をトリムします。
     *
     * ex)
     * Strings::latrim('1.2.3', '.');        //=> '1'
     * Strings::latrim('1.2.3', '.', false); //=> '1.'
     *
     * @param string|null $str トリム対象
     * @param string $delimiter 区切り文字
     * @param bool $remove_delimiter 区切り文字削除／true : 削除する, false : 削除しない （デフォルト：true）
     * @return string|null トリム文字列
     */
    public static function latrim(?string $str, string $delimiter, bool $remove_delimiter = true) : ?string
    {
        $end = strpos($str, $delimiter);
        if ($end === false) {
            return $str;
        }
        return mb_substr($str, 0, $end + ($remove_delimiter ? 0 : mb_strlen($delimiter)));
    }
    
    /**
     * 最も右側にある指定文字列より左側(Right Before)の文字をトリムします。
     *
     * ex)
     * Strings::rbtrim('1.2.3', '.');        //=> '3'
     * Strings::rbtrim('1.2.3', '.', false); //=> '.3'
     *
     * @param string|null $str トリム対象
     * @param string $delimiter 区切り文字
     * @param bool $remove_delimiter 区切り文字削除／true : 削除する, false : 削除しない （デフォルト：true）
     * @return string|null トリム文字列
     */
    public static function rbtrim(?string $str, string $delimiter, bool $remove_delimiter = true) : ?string
    {
        $start = strrpos($str, $delimiter);
        if ($start === false) {
            return $str;
        }
        return mb_substr($str, $start + ($remove_delimiter ? mb_strlen($delimiter) : 0));
    }
    
    /**
     * 最も右側にある指定文字列より右側(Right After)の文字をトリムします。
     *
     * ex)
     * Strings::ratrim('1.2.3', '.');        //=> '1.2'
     * Strings::ratrim('1.2.3', '.', false); //=> '1.2.'
     *
     * @param string|null $str トリム対象
     * @param string $delimiter 区切り文字
     * @param bool $remove_delimiter 区切り文字削除／true : 削除する, false : 削除しない （デフォルト：true）
     * @return string|null トリム文字列
     */
    public static function ratrim(?string $str, string $delimiter, bool $remove_delimiter = true) : ?string
    {
        $end = strrpos($str, $delimiter);
        if ($end === false) {
            return $str;
        }
        return mb_substr($str, 0, $end + ($remove_delimiter ? 0 : mb_strlen($delimiter)));
    }
    
    /**
     * Trim the repetition of the specified character string at the left end.
     *
     * ex)
     * Strings::ltrim('   abc   ');               //=> 'abc   '
     * Strings::ltrim('111abc111', '1');          //=> 'abc111'
     * Strings::ltrim('12121abc21212', '12');     //=> '1abc21212'
     * Strings::ltrim('　　　全角　　　', '　');    //=> '全角　　　'
     * Strings::ltrim('　　　全角　　　', '　', 2); //=> '　全角　　　'
     *
     * @param string|null $str
     * @param string $prefix (default: ' ')
     * @param int|null $max (default: null)
     * @return string|null トリム文字列
     */
    public static function ltrim(?string  $str, string $prefix = ' ', ?int $max = null) : ?string
    {
        $repeat = $max === null ? "*" : "{0,{$max}}" ;
        return $str === null ? null : preg_replace("/\A(".preg_quote($prefix, '/')."){$repeat}/u", '', $str);
    }
    
    /**
     * Trim the repetition of the specified character string at the right end.
     *
     * ex)
     * Strings::rtrim('   abc   ');               //=> '   abc'
     * Strings::rtrim('111abc111', '1');          //=> '111abc'
     * Strings::rtrim('12121abc21212', '12');     //=> '12121abc2'
     * Strings::rtrim('　　　全角　　　', '　');    //=> '　　　全角'
     * Strings::rtrim('　　　全角　　　', '　', 2); //=> '　　　全角　'
     *
     * @param string|null $str トリム対象
     * @param string $suffix トリム文字列
     * @return string|null トリム文字列
     */
    public static function rtrim(?string $str, string $suffix = ' ', ?int $max = null) : ?string
    {
        $repeat = $max === null ? "*" : "{0,{$max}}" ;
        return $str === null ? null : preg_replace("/(".preg_quote($suffix, '/')."){$repeat}\z/u", '', $str);
    }
    
    /**
     * Trim the repetition of the given character string at the both end.
     *
     * ex)
     * Strings::rtrim('   abc   ');               //=> 'abc'
     * Strings::rtrim('111abc111', '1');          //=> 'abc'
     * Strings::rtrim('12121abc21212', '12');     //=> '1abc2'
     * Strings::rtrim('　　　全角　　　', '　');    //=> '全角'
     * Strings::rtrim('　　　全角　　　', '　', 2); //=> '　全角　'
     *
     * @param string|null $str
     * @param string $deletion
     * @return string|null
     */
    public static function trim(?string $str, string $deletion = ' ', ?int $max = null) : ?string
    {
        return static::ltrim(static::rtrim($str, $deletion, $max), $deletion, $max);
    }

    /**
     * Trim the space letters including multi byte space letters from given string.
     *
     * @param string|null $str
     * @return string|null
     */
    public static function mbtrim(?string $str) : ?string
    {
        return $str === null ? null : preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $str);
    }

    /**
     * 指定の文字列 [$haystack] が指定の文字列 [$needle] で始まるか検査します。
     *
     * ex)
     * Strings::startsWith('abc123', 'abc'); //=> true
     *
     * @param string|null $haystack 検査対象文字列
     * @param string $needle   被検査文字列
     * @return bool true : 始まる／false : 始まらない
     */
    public static function startsWith(?string $haystack, string $needle) : bool
    {
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }
    
    /**
     * 指定の文字列 [$haystack] が指定の文字列 [$needle] で終わるか検査します。
     *
     * ex)
     * Strings::endsWith('abc123', '123'); //=> true
     *
     * @param string|null $haystack 検査対象文字列
     * @param string $needle 被検査文字列
     * @return bool true : 終わる／false : 終わらない
     */
    public static function endsWith(?string $haystack, string $needle) : bool
    {
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }
    
    /**
     * 機種依存文字が含まれるかチェックします。
     *
     * ex)
     * Strings::checkDependenceChar('あ①♬㈱♥');                //=> [2 => '♬', 4 => '♥']
     * Strings::checkDependenceChar('あ①♬㈱♥', 'iso-2022-jp'); //=> [1 => '①', 2 => '♬', 3 => '㈱', 4 => '♥']
     * Strings::checkDependenceChar('あ①♬㈱♥', 'UTF-8');       //=> []
     *
     * @param string|null $text 検査対象文字列
     * @param string $encode 機種依存チェックを行う文字コード（デフォルト： sjis-win）
     * @return array 機種依存文字の配列
     */
    public static function checkDependenceChar(?string $text, string $encode = 'sjis-win') : array
    {
        $org  = $text;
        $conv = mb_convert_encoding(mb_convert_encoding($text, $encode, 'UTF-8'), 'UTF-8', $encode);
        if (strlen($org) != strlen($conv)) {
            return array_diff(self::toCharArray($org), self::toCharArray($conv));
        }
        
        return [];
    }
    
    /**
     * 文字列を文字の配列に変換します。
     *
     * ex)
     * Strings::toCharArray('abc'); //=> ['a', 'b', 'c']
     *
     * @param string|null $string 文字列
     * @return array 文字の配列
     */
    public static function toCharArray(?string $string) : array
    {
        return preg_split("//u", $string, -1, PREG_SPLIT_NO_EMPTY);
    }
    
    /**
     * 指定の文字列をインデントします。
     * ※対象の文字列が空の場合もインデントされます。
     *
     * @param string|null $string インデント対象文字列
     * @param int $depth インデント文字数（デフォルト：1）
     * @param string $char インデント文字（デフォルト：'\t'）
     */
    public static function indent(?string $string, int $depth = 1, string $char = "\t") : ?string
    {
        if ($string === null) {
            return null;
        }
        $indent  = str_repeat($char, $depth);
        $indened = (self::startsWith($string, "\n") ? '' : $indent).str_replace("\n", "\n{$indent}", $string);
        return self::endsWith($indened, "\n{$indent}") ? mb_substr($indened, 0, \mb_strlen($indened) - \mb_strlen($indent)) : $indened ;
    }

    /**
     * It checks whether the string contains all (or at least N) the given search strings.
     *
     * @param string|null $string
     * @param string|array $searches
     * @param int $at_least (default: null)
     * @return bool
     */
    public static function contains(?string $string, $searches, ?int $at_least = null) : bool
    {
        $searches = (array)$searches;
        if ($string === null || $searches === []) {
            return false;
        }
        if ($at_least === null) {
            foreach ($searches as $search) {
                if (!static::_contains($string, $search)) {
                    return false;
                }
            }
            return true;
        }

        $count = 0;
        foreach ($searches as $search) {
            $count += static::_contains($string, $search) ? 1 : 0 ;
        }
        return $at_least <= $count;
    }

    /**
     * It checks whether the specified character string is included in the target character string.
     *
     * @param string|null $string
     * @param string $searches
     * @return bool
     */
    protected static function _contains(?string $string, string $search) : bool
    {
        return $search === '' ? true : strpos($string, $search) !== false ;
    }
    
    /**
     * Cut the string and append to ellipsis that become a given length.
     *
     * @param string|null $string
     * @param integer $length
     * @param string $ellipsis (default: '...')
     * @return string|null
     */
    public static function cut(?string $string, int $length, string $ellipsis = '...') : ?string
    {
        if ($string === null) {
            return null;
        }
        if (mb_strlen($string) <= $length) {
            return $string;
        }
        $max = $length - mb_strlen($ellipsis);
        if ($max < 1) {
            throw new \LogicException("Invalid cut length and ellipsis. The length must be longer than ellipsis.");
        }
        return mb_substr($string, 0, $max).$ellipsis;
    }
}
