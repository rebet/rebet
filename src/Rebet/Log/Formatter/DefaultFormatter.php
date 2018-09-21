<?php
namespace Rebet\Log\Formatter;

use Rebet\Common\StringUtil;
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
            $message = StringUtil::rtrim(print_r($message, true), "\n");
        }
        $prefix = $log->now->format('Y-m-d H:i:s.u')." ".getmypid()." [{$log->level}] ";
        $body   = $prefix.$message; // StringUtil::indent($message, 1, $prefix);
        
        if ($log->var) {
            $body .= StringUtil::indent(
                "\n*** VAR ***".
                "\n".StringUtil::rtrim(print_r($log->var, true), "\n"),
                1,
                "== " //"{$prefix}== "
            );
        }
        
        if ($log->extra) {
            $body .= StringUtil::indent(
                "\n*** EXTRA ***".
                "\n".StringUtil::rtrim(print_r($log->extra, true), "\n"),
                1,
                "-- " //"{$prefix}-- "
            );
        }
        
        if ($log->level->equals(LogLevel::DEBUG())) {
            $body .= StringUtil::indent(
                "\n*** DEBUG TRACE ***".
                "\n".Log::traceToString(debug_backtrace(), false),
                1,
                ".. " //"{$prefix}.. "
            );
        }
        
        if ($log->error) {
            if ($log->error instanceof \Throwable) {
                $body .= StringUtil::indent(
                    "\n*** STACK TRACE ***".
                    "\n{$log->error}",
                    1,
                    "** " //"{$prefix}** "
                );
            } else {
                $trace = '';
                if ($log->level->higherEqual(LogLevel::ERROR())) {
                    $trace = "\n".Log::traceToString(debug_backtrace(), true);
                }
                $body .= StringUtil::indent(
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
}
