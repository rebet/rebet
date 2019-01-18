<?php
namespace Rebet\Common;

use Rebet\Common\Exception\LogicException;

/**
 * Strings Utility Class
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
     * Trim characters before the leftmost given delimiter string.
     *
     * ex)
     * Strings::lbtrim('1.2.3', '.');        //=> '2.3'
     * Strings::lbtrim('1.2.3', '.', false); //=> '.2.3'
     *
     * @param string|null $str
     * @param string $delimiter
     * @param bool $remove_delimiter when true then remove delimiter (default: true)
     * @return string|null
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
     * Trim characters after the leftmost given delimiter string.
     *
     * ex)
     * Strings::latrim('1.2.3', '.');        //=> '1'
     * Strings::latrim('1.2.3', '.', false); //=> '1.'
     *
     * @param string|null $str
     * @param string $delimiter
     * @param bool $remove_delimiter when true then remove delimiter (default: true)
     * @return string|null
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
     * Trim characters before the rightmost given delimiter string.
     *
     * ex)
     * Strings::rbtrim('1.2.3', '.');        //=> '3'
     * Strings::rbtrim('1.2.3', '.', false); //=> '.3'
     *
     * @param string|null $str
     * @param string $delimiter
     * @param bool $remove_delimiter when true then remove delimiter (default: true)
     * @return string|null
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
     * Trim characters after the rightmost given delimiter string.
     *
     * ex)
     * Strings::ratrim('1.2.3', '.');        //=> '1.2'
     * Strings::ratrim('1.2.3', '.', false); //=> '1.2.'
     *
     * @param string|null $str
     * @param string $delimiter
     * @param bool $remove_delimiter when true then remove delimiter (default: true)
     * @return string|null
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
     * @return string|null
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
     * @param string|null $str
     * @param string $suffix (default: ' ')
     * @param int|null $max (default: null)
     * @return string|null
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
     * @param string $deletion (default: ' ')
     * @param int|null $max (default: null)
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
     * It checks whether the given string $haystack starts with the $needle.
     *
     * ex)
     * Strings::startsWith('abc123', 'abc'); //=> true
     *
     * @param string|null $haystack
     * @param string $needle
     * @return bool
     */
    public static function startsWith(?string $haystack, string $needle) : bool
    {
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }
    
    /**
     * It checks whether the given string $haystack ends with the $needle.
     *
     * ex)
     * Strings::endsWith('abc123', '123'); //=> true
     *
     * @param string|null $haystack
     * @param string $needle
     * @return bool
     */
    public static function endsWith(?string $haystack, string $needle) : bool
    {
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }
    
    /**
     * It checks whether model dependent characters are included.
     *
     * ex)
     * Strings::checkDependenceChar('あ①♬㈱♥');                //=> [2 => '♬', 4 => '♥']
     * Strings::checkDependenceChar('あ①♬㈱♥', 'iso-2022-jp'); //=> [1 => '①', 2 => '♬', 3 => '㈱', 4 => '♥']
     * Strings::checkDependenceChar('あ①♬㈱♥', 'UTF-8');       //=> []
     *
     * @param string|null $text
     * @param string $encode (default: 'sjis-win')
     * @return array of dependence chars
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
     * Converts a string to an array of characters.
     *
     * ex)
     * Strings::toCharArray('abc'); //=> ['a', 'b', 'c']
     *
     * @param string|null $string
     * @return array
     */
    public static function toCharArray(?string $string) : array
    {
        return preg_split("//u", $string, -1, PREG_SPLIT_NO_EMPTY);
    }
    
    /**
     * Indent the given string.
     * Note: Indented even when the target character string is empty.
     *
     * @param string|null $string
     * @param string $char for indent (default: '\t')
     * @param int $depth (default: 1)
     */
    public static function indent(?string $string, string $char = "\t", int $depth = 1) : ?string
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
     * Delete N characters from the left.
     *
     * @param string|null $string
     * @param integer $length
     * @param string $encoding (default: 'UTF-8')
     * @return string|null
     */
    public static function lcut(?string $string, int $length, string $encoding = 'UTF-8') : ?string
    {
        if ($string === null) {
            return null;
        }
        if (0 >= $length) {
            return $string;
        }
        if (mb_strlen($string) <= $length) {
            return '';
        }
        return mb_substr($string, $length, null, $encoding);
    }

    /**
     * Delete N characters from the right.
     *
     * @param string|null $string
     * @param integer $length
     * @param string $encoding (default: 'UTF-8')
     * @return string|null
     */
    public static function rcut(?string $string, int $length, string $encoding = 'UTF-8') : ?string
    {
        if ($string === null) {
            return null;
        }
        if (0 >= $length) {
            return $string;
        }
        if (mb_strlen($string) <= $length) {
            return '';
        }
        return mb_substr($string, 0, $length * -1, $encoding);
    }

    /**
     * Clip the string and append to ellipsis that become a given length.
     *
     * @param string|null $string
     * @param integer $length
     * @param string $ellipsis (default: '...')
     * @return string|null
     */
    public static function clip(?string $string, int $length, string $ellipsis = '...') : ?string
    {
        if ($string === null) {
            return null;
        }
        if (mb_strlen($string) <= $length) {
            return $string;
        }
        $max = $length - mb_strlen($ellipsis);
        if ($max < 1) {
            throw LogicException::by("Invalid clip length and ellipsis. The length must be longer than ellipsis.");
        }
        return mb_substr($string, 0, $max).$ellipsis;
    }

    /**
     * It checks the given string will match regex patterns at least one.
     *
     * @param string|null $string
     * @param string|array $patterns
     * @return boolean
     */
    public static function match(?string $string, $patterns) : bool
    {
        if ($string === null) {
            return false;
        }
        $patterns = (array)$patterns;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $string)) {
                return true;
            }
        }
        return false;
    }

    /**
     * It checks the given string will match shell's wildcard patterns at least one.
     *
     * @param string|null $string
     * @param string|array $patterns
     * @return boolean
     */
    public static function wildmatch(?string $string, $patterns) : bool
    {
        if ($string === null) {
            return false;
        }
        $patterns = (array)$patterns;
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $string)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert debug_backtrace to string.
     *
     * @param array $trace
     * @param boolean $withArgs (default: false)
     * @return string
     */
    public static function traceToString(array $trace, bool $withArgs = false) : string
    {
        $trace = array_reverse($trace);
        array_pop($trace); // Remove self method stack
        array_walk($trace, function (&$value, $key) use ($withArgs) {
            $value = "#{$key} ".
            (empty($value['file']) ? "" : " ".$value['file']."(".$value['line']."): ").
            (empty($value['class']) ? "" : $value['class']."::").
            $value['function'].
            ($withArgs && !empty($value['args']) ? '('.static::argsToString($value['args']).')' : "()")
            ;
        });
        
        return empty($trace) ? "" : join("\n", $trace) ;
    }

    /**
     * Convert args to string.
     *
     * @param mixed $args
     * @param integer $length (default: 20)
     * @param string $ellipsis (default: '...')
     * @return string
     */
    public static function argsToString($args, int $length = 20, string $ellipsis = '...') : string
    {
        $args      = (array)$args;
        $describes = '';
        foreach ($args as $key => $arg) {
            $describes .= static::argToString($arg, $length, $ellipsis).", ";
        }
        return Strings::rtrim($describes, ', ');
    }
    
    /**
     * Convert arg to string.
     *
     * @param mixed $arg
     * @param integer $length (default: 20)
     * @param string $ellipsis (default: '...')
     * @return string
     */
    protected static function argToString($arg, int $length = 20, string $ellipsis = '...', $array_scanning = true) : string
    {
        if ($arg === null) {
            return 'null';
        }
        if (is_string($arg)) {
            return Strings::clip($arg, $length, $ellipsis);
        }
        if (is_scalar($arg)) {
            return Strings::clip((string)$arg, $length, $ellipsis);
        }
        if (is_resource($arg)) {
            return Strings::clip('*'.get_resource_type($arg).'*', $length, $ellipsis);
        }
        if (method_exists($arg, '__toString')) {
            return Strings::clip($arg->__toString(), $length, $ellipsis);
        }
        if (is_object($arg) && $arg instanceof \JsonSerializable) {
            $json = $arg->jsonSerialize();
            if (is_scalar($json)) {
                return Strings::clip((string)$json, $length, $ellipsis);
            }
        }
        if (is_array($arg) && $array_scanning) {
            $describes = '';
            foreach ($arg as $key => $value) {
                $describes .= "{$key} => ".Strings::clip(static::argToString($value, $length, false), $length, $ellipsis).", ";
            }
            return '['.Strings::rtrim($describes, ', ').']';
        }
        
        $class         = new \ReflectionClass($arg);
        $namespace     = $class->getNamespaceName();
        $namespace_cut = Strings::rbtrim($namespace, '\\');
        $namespace     = $namespace === $namespace_cut ? $namespace : "..\\{$namespace_cut}" ;
        return $namespace.'\\'.$class->getShortName();
    }

    /**
     * Split the string by given delimiter and pad the result array.
     * This behavior may be useful for assigning split results to variables using list() or [].
     *
     * @param string|null $string
     * @param string $delimiter
     * @param integer $size
     * @param mixed $padding (default: null)
     * @return array
     */
    public static function split(?string $string, string $delimiter, int $size, $padding = null) : array
    {
        return array_pad($string === null ? [] : explode($delimiter, $string, $size), $size, $padding);
    }
}
