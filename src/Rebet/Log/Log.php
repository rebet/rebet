<?php
namespace Rebet\Log;

use Rebet\Config\Configable;
use Rebet\DateTime\DateTime;

/**
 * ロガー クラス
 * 
 * 任意のハンドラとプラグインを組み合わせてログ出力を行います。
 * 
 * パッケージで用意済みのハンドラ＆プラグインには下記のものがあります。
 * ※これらのハンドラ及びプラグインは順次追加していきます。
 * 
 * ハンドラ
 * --------------------
 * @see \Rebet\Log\Handler\StderrHandler::class （ライブラリデフォルト）
 * @see \Rebet\Log\Handler\FileHandler::class
 * 
 * プラグイン（ライブラリデフォルト：無指定）
 * --------------------
 * @see \Rebet\Log\Plugin\WebDisplayPlugin::class
 * 
 * @todo 各種ハンドラ＆プラグイン追加
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Log {
    use Configable;
    public static function defaultConfig() {
        return [
            'log_handler' => \Rebet\Log\Handler\StderrHandler::class,
            'log_plugins' => [],
        ];
    }

    // エラーレベル定義
    const LEVEL_FATAL = 0;
    const LEVEL_ERROR = 1;
    const LEVEL_WARN  = 2;
    const LEVEL_INFO  = 3;
    const LEVEL_DEBUG = 4;
    const LEVEL_TRACE = 5;
    
    /**
     * インスタンス化禁止
     */
    private function __construct() {}

    /**
     * ログハンドラ
     * @var Rebet\Log\Handler\LogHandler
     */
    public static $HANDLER = null;

    /**
     * ログプラグイン
     * @var array
     */
    public static $PLUGINS = null;

    /**
     * TRACE レベルログを出力します。
     * 
     * @param mixed $message ログ内容
     * @param array $context コンテキスト（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @return void
     */
    public static function trace($message, array $context = [], $error = null) : void {
        self::log(LogLevel::TRACE(), $message, $context, $error);
    }
    
    /**
     * DEBUG レベルログを出力します。
     *
     * @param mixed $message ログ内容
     * @param array $context コンテキスト（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @return void
     */
    public static function debug($message, array $context = [], $error = null) : void {
        self::log(LogLevel::DEBUG(), $message, $context, $error);
    }
    
    /**
     * INFO レベルログを出力します。
     * 
     * @param mixed $message ログ内容
     * @param array $context コンテキスト（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @return void
     */
    public static function info($message, array $context = [], $error = null) : void {
        self::log(LogLevel::INFO(), $message, $context, $error);
    }
    
    /**
     * WARN レベルログを出力します。
     * 
     * @param mixed $message ログ内容
     * @param array $context コンテキスト（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @return void
     */
    public static function warn($message, array $context = [], $error = null) : void {
        self::log(LogLevel::WARN(), $message, $context, $error);
    }
    
    /**
     * ERROR レベルログを出力します。
     * 
     * @param mixed $message ログ内容
     * @param array $context コンテキスト（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @return void
     */
    public static function error($message, array $context = [], $error = null) : void {
        self::log(LogLevel::ERROR(), $message, $context, $error);
    }
    
    /**
     * FATAL レベルログを出力します。
     * 
     * @param mixed $message ログ内容
     * @param array $context コンテキスト（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @return void
     */
    public static function fatal($message, array $context = [], $error = null) : void {
        self::log(LogLevel::FATAL(), $message, $context, $error);
    }
    
    /**
     * メモリ使用量を出力します。
     * 
     * @todo プラグイン化すべきか？
     * 
     * @param string $message   ログメッセージ（デフォルト: 空文字）
     * @param int    $decimals  メモリ[MB]の小数点桁数（デフォルト: 2）
     * @return void
     */
    public static function memory(string $message = '', int $decimals = 2) {
        $current = number_format(memory_get_usage() / 1048576, $decimals);
        $peak    = number_format(memory_get_peak_usage() / 1048576, $decimals);
        $message = empty($message) ? "" : "{$message} : " ;
        $message = $message."Memory {$current} MB / Peak Memory {$peak} MB";
        self::log(LogLevel::INFO(), $message);
    }
    
    /**
     * ロガーを初期化します。
     */
    private static function init() {
        if(self::$HANDLER === null) {
            $handler_class = self::config('log_handler');
            self::$HANDLER = $handler_class::create();
        }

        if(self::$PLUGINS === null) {
            self::$PLUGINS = [];
            foreach (self::config('log_plugins') as $plugin_class) {
                self::$PLUGINS[] = $plugin_class::create();
            }
        }
    }

    /**
     * ログを出力します。
     * 
     * @param LogLevel $level ログレベル
     * @param mixed $message ログ内容
     * @param array $context コンテキスト（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @return void
     */
    private static function log(LogLevel $level, $message, array $context = [], $error = null) : void {
        $now   = DateTime::now();
        $extra = [];

        self::init();

        foreach (self::$PLUGINS as $plugin) {
            $plugin->prehook(self::$HANDLER, $now, $level, $extra);
        }

        $formatted_log = self::$HANDLER->handle($now, $level, $message, $context, $error, $extra);
        
        if($formatted_log !== null) {
            foreach (self::$PLUGINS as $plugin) {
                $plugin->posthook(self::$HANDLER, $now, $level, $formatted_log);
            }
        }
    }
    
    /**
     * エラーハンドラー用のエラーハンドル
     * 
     * @param array $error error_get_last 形式の配列
     * @return void
     */
    public static function errorHandle(array $error) : void {
        self::log(LogLevel::errorTypeOf($error['type']), "{$error['message']} ({$error['file']}:{$error['line']})", [], $error);
    }
    
    /**
     * debug_backtrace を文字列形式に変換します。
     * 
     * @param array $trace debug_backtrace
     * @param boolean true : 引数記載有り／false : 引数記載無し（デフォルト）
     * @return string デバックバックトレース文字列
     */
    public static function traceToString(array $trace, bool $withArgs = false) : string {
        $trace = array_reverse($trace);
        array_pop($trace); // Remove self method stack
        array_walk($trace, function(&$value, $key) use ($withArgs) {
            $value = "#{$key} ".
            (empty($value['class']) ? "" : $value['class']."@").
            $value['function'].
            (empty($value['file']) ? "" : " (".$value['file'].":".$value['line'].")").
            ($withArgs && !empty($value['args']) ? "\n-- ARGS --\n".print_r($value['args'], true) : "" )
            ;
        });
        
        return empty($trace) ? "" : join("\n", $trace) ;
    }
}

// エラーハンドラ登録
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    Log::errorHandle(['type' => $errno, 'message' => $errstr, 'file' => $errfile, 'line' => $errline]);
});

// シャットダウンハンドラ登録
register_shutdown_function(function() {
    $error = error_get_last();
    if($error) { Log::errorHandle($error); }
    foreach (Log::$PLUGINS as $plugin) {
        $plugin->shutdown();
    }
    Log::$HANDLER->shutdown();
});
