<?php
namespace Rebet\Log\Formatter;

use Rebet\Common\Strings;
use Rebet\Log\Log;
use Rebet\Log\LogContext;
use Rebet\Log\LogLevel;

/**
 * デフォルトログフォーマッタ インターフェース
 *
 * Rebet にて標準で用意されたデフォルトフォーマッタです。
 *
 * @todo *** DEBUG TRACE *** は無くてもいいのではないか？　又は出力可否を制御できるべきか？
 * @todo ログフォーマットの導入
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class DefaultFormatter implements LogFormatter
{
    /**
      * ログをフォーマットします。
      *
      * @param LogContext $log ログコンテキスト
      * @return string|array 整形済みログ情報
      */
    public function format(LogContext $log)
    {
        $body    = '';
        $message = $log->message;

        if (!is_string($message) && !method_exists($message, '__toString')) {
            $message = Strings::rtrim(print_r($message, true), "\n");
        }
        $prefix = $log->now->format('Y-m-d H:i:s.u')." ".getmypid()." [{$log->level}] ";
        $body   = $prefix.$message; // Strings::indent($message, 1, $prefix);
        
        if ($log->var) {
            $body .= Strings::indent(
                "\n*** VAR ***".
                "\n".Strings::rtrim(print_r($log->var, true), "\n"),
                1,
                "== " //"{$prefix}== "
            );
        }
        
        if ($log->extra) {
            $body .= Strings::indent(
                "\n*** EXTRA ***".
                "\n".Strings::rtrim(print_r($log->extra, true), "\n"),
                1,
                "-- " //"{$prefix}-- "
            );
        }
        
        if ($log->level->equals(LogLevel::DEBUG())) {
            $body .= Strings::indent(
                "\n*** DEBUG TRACE ***".
                "\n".self::traceToString(debug_backtrace(), false),
                1,
                ".. " //"{$prefix}.. "
            );
        }
        
        if ($log->error) {
            if ($log->error instanceof \Throwable) {
                // $body .= Strings::indent(
                //     "\n*** STACK TRACE ***".
                //     "\n{$log->error}",
                //     1,
                //     "** " //"{$prefix}** "
                // );
            } else {
                $trace = '';
                if ($log->level->higherEqual(LogLevel::ERROR())) {
                    $trace = "\n".self::traceToString(debug_backtrace(), true);
                }
                $body .= Strings::indent(
                    "\n*** STACK TRACE ***".
                    "\n{$log->error['message']} <{$log->error['type']}> ({$log->error['file']}:{$log->error['line']})".
                    $trace,
                    1,
                    "** " //"{$prefix}** "
               );
            }
        }
        
        return $body;
    }
    
    /**
     * Convert debug_backtrace to string.
     *
     * @param array $trace
     * @param boolean $withArgs (default: false)
     * @return string
     */
    protected static function traceToString(array $trace, bool $withArgs = false) : string
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
     * @param array $args
     * @param integer $length (default: 20)
     * @param string $ellipsis (default: '...')
     * @return string
     */
    protected static function argsToString(array $args, int $length = 20, string $ellipsis = '...') : string
    {
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
            return Strings::cut($arg, $length, $ellipsis);
        }
        if (is_scalar($arg)) {
            return Strings::cut((string)$arg, $length, $ellipsis);
        }
        if (method_exists($arg, '__toString')) {
            return Strings::cut($arg->__toString(), $length, $ellipsis);
        }
        if (is_object($arg) && $arg instanceof \JsonSerializable) {
            $json = $arg->jsonSerialize();
            if (is_scalar($json)) {
                return Strings::cut((string)$json, $length, $ellipsis);
            }
        }
        if (is_array($arg) && $array_scanning) {
            $describes = '';
            foreach ($arg as $key => $value) {
                $describes .= "{$key} => ".Strings::cut(static::argToString($value, $length, false), $length, $ellipsis).", ";
            }
            return '['.Strings::rtrim($describes, ', ').']';
        }
        
        $class         = new \ReflectionClass($arg);
        $namespace     = $class->getNamespaceName();
        $namespace_cut = Strings::rbtrim($namespace, '\\');
        $namespace     = $namespace === $namespace_cut ? $namespace : "..\\{$namespace_cut}" ;
        return $namespace.'\\'.$class->getShortName();
    }
}
