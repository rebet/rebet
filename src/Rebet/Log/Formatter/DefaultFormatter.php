<?php
namespace Rebet\Log\Formatter;

use Rebet\Common\StringUtil;
use Rebet\DateTime\DateTime;
use Rebet\Log\Log;
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
class DefaultFormatter implements LogFormatter {

    /**
     * ログフォーマッタを作成します。
     */
    public static function create() : LogFormatter {
        return new static();
    }

   /**
     * ログをフォーマットします。
     * 
     * @param DateTime $now 現在時刻
     * @param LogLevel $level ログレベル
     * @param mixed $message ログ内容
     * @param array $context コンテキスト（デフォルト：null）
     * @param \Throwable|array $error 例外 or error_get_last 形式の配列（デフォルト：null）
     * @param array $extra エキストラ情報（デフォルト：null）
     * @return string|array 整形済みログ情報
     */
    public function format(DateTime $now, LogLevel $level, $message, array $context = [], $error = null, array $extra = []) {
        $body = '';

        if(!is_string($message) && !method_exists($message, '__toString')) { $message = StringUtil::rtrim(print_r($message, true), "\n"); }
        $prefix = $now->format('Y-m-d H:i:s.u')." ".getmypid()." [{$level}] ";
        $body   = $prefix.$message; // StringUtil::indent($message, 1, $prefix);
        
        if($context) {
            $body .= StringUtil::indent(
                "\n*** CONTEXT ***".
                "\n".StringUtil::rtrim(print_r($context, true), "\n"), 
                1,
                "== " //"{$prefix}== "
            );
        }
        
        if($extra) {
            $body .= StringUtil::indent(
                "\n*** EXTRA ***".
                "\n".StringUtil::rtrim(print_r($extra, true), "\n"), 
                1,
                "-- " //"{$prefix}-- "
            );
        }
        
        if($level->equals(LogLevel::DEBUG())) {
            $body .= StringUtil::indent(
                "\n*** DEBUG TRACE ***".
                "\n".Log::traceToString(debug_backtrace(), false),
                1,
                ".. " //"{$prefix}.. "
            );
        }
        
        if($error) {
            if($error instanceof \Throwable) {
                $body .= StringUtil::indent(
                    "\n*** STACK TRACE ***".
                    "\n{$error}",
                    1,
                    "** " //"{$prefix}** "
                );
            } else {
                $trace = '';
                if($level->higherEqual(LogLevel::ERROR())) {
                    $trace = "\n".Log::traceToString(debug_backtrace(), true);
                }
                $body .= StringUtil::indent(
                    "\n*** STACK TRACE ***".
                    "\n{$error['message']} <{$error['type']}> ({$error['file']}:{$error['line']})".
                    $trace,
                    1,
                    "** " //"{$prefix}** "
               );
            }
        }
        
        return $body;
    }
}
