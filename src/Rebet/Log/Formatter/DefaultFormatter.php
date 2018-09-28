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
                $body .= Strings::indent(
                    "\n*** STACK TRACE ***".
                    "\n{$log->error}",
                    1,
                    "** " //"{$prefix}** "
                );
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
     * debug_backtrace を文字列形式に変換します。
     *
     * @param array $trace debug_backtrace
     * @param boolean true : 引数記載有り／false : 引数記載無し（デフォルト）
     * @return string デバックバックトレース文字列
     */
    protected static function traceToString(array $trace, bool $withArgs = false) : string
    {
        $trace = array_reverse($trace);
        array_pop($trace); // Remove self method stack
        array_walk($trace, function (&$value, $key) use ($withArgs) {
            $value = "#{$key} ".
            (empty($value['class']) ? "" : $value['class']."@").
            $value['function'].
            (empty($value['file']) ? "" : " (".$value['file'].":".$value['line'].")").
            ($withArgs && !empty($value['args']) ? "\n-- ARGS --\n".print_r($value['args'], true) : "")
            ;
        });
        
        return empty($trace) ? "" : join("\n", $trace) ;
    }
}
