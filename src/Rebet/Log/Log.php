<?php
namespace Rebet\Log;

use Rebet\Config\Configurable;
use Rebet\DateTime\DateTime;
use Rebet\Pipeline\Pipeline;

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
 * ミドルウェア（ライブラリデフォルト：無指定）
 * --------------------
 * @see \Rebet\Log\Middleware\WebDisplayMiddleware::class
 *
 * @todo 各種ハンドラ＆ミドルウェア追加
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Log
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'log_handler'     => \Rebet\Log\Handler\StderrHandler::class,
            'log_middlewares' => [],
        ];
    }

    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * ログハンドラ
     * @var Rebet\Log\Handler\LogHandler
     */
    private static $HANDLER = null;

    /**
     * ログミドルウェアパイプライン
     * @var Revet\Pipeline\Pipeline
     */
    private static $PIPELINE = null;

    /**
     * TRACE レベルログを出力します。
     *
     * @param mixed $message ログ内容
     * @param array $var 変数（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @return void
     */
    public static function trace($message, array $var = [], $error = null) : void
    {
        self::log(LogLevel::TRACE(), $message, $var, $error);
    }
    
    /**
     * DEBUG レベルログを出力します。
     *
     * @param mixed $message ログ内容
     * @param array $var 変数（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @return void
     */
    public static function debug($message, array $var = [], $error = null) : void
    {
        self::log(LogLevel::DEBUG(), $message, $var, $error);
    }
    
    /**
     * INFO レベルログを出力します。
     *
     * @param mixed $message ログ内容
     * @param array $var 変数（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @return void
     */
    public static function info($message, array $var = [], $error = null) : void
    {
        self::log(LogLevel::INFO(), $message, $var, $error);
    }
    
    /**
     * WARN レベルログを出力します。
     *
     * @param mixed $message ログ内容
     * @param array $var 変数（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @return void
     */
    public static function warn($message, array $var = [], $error = null) : void
    {
        self::log(LogLevel::WARN(), $message, $var, $error);
    }
    
    /**
     * ERROR レベルログを出力します。
     *
     * @param mixed $message ログ内容
     * @param array $var 変数（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @return void
     */
    public static function error($message, array $var = [], $error = null) : void
    {
        self::log(LogLevel::ERROR(), $message, $var, $error);
    }
    
    /**
     * FATAL レベルログを出力します。
     *
     * @param mixed $message ログ内容
     * @param array $var 変数（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @return void
     */
    public static function fatal($message, array $var = [], $error = null) : void
    {
        self::log(LogLevel::FATAL(), $message, $var, $error);
    }
    
    /**
     * メモリ使用量を出力します。
     *
     * @todo ミドルウェア化すべきか？
     *
     * @param string $message   ログメッセージ（デフォルト: 空文字）
     * @param int    $decimals  メモリ[MB]の小数点桁数（デフォルト: 2）
     * @return void
     */
    public static function memory(string $message = '', int $decimals = 2)
    {
        $current = number_format(memory_get_usage() / 1048576, $decimals);
        $peak    = number_format(memory_get_peak_usage() / 1048576, $decimals);
        $message = empty($message) ? "" : "{$message} : " ;
        $message = $message."Memory {$current} MB / Peak Memory {$peak} MB";
        self::log(LogLevel::INFO(), $message);
    }
    
    /**
     * ロガーを初期化します。
     */
    public static function init()
    {
        self::shutdown();
        self::$HANDLER  = self::configInstantiate('log_handler');
        self::$PIPELINE = (new Pipeline())->through(self::config('log_middlewares', false, []))->then(self::$HANDLER);
    }

    /**
     * ロガーをシャットダウンします。
     *
     * @return void
     */
    public static function shutdown()
    {
        if (self::$PIPELINE !== null) {
            Log::$PIPELINE->invoke('shutdown');
        }
        if (self::$HANDLER !== null) {
            Log::$HANDLER->shutdown();
        }
    }

    /**
     * ログを出力します。
     *
     * @param LogLevel $level ログレベル
     * @param mixed $message ログ内容
     * @param array $var 変数（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @return void
     */
    private static function log(LogLevel $level, $message, array $var = [], $error = null) : void
    {
        if (self::$PIPELINE === null) {
            self::init();
        }
        $context = new LogContext(DateTime::now(), $level, $message, $var, $error);
        self::$PIPELINE->send($context);
    }
    
    /**
     * エラーハンドラー用のエラーハンドル
     *
     * @param array $error error_get_last 形式の配列
     * @return void
     */
    public static function errorHandle(array $error) : void
    {
        self::log(LogLevel::errorTypeOf($error['type']), "{$error['message']} ({$error['file']}:{$error['line']})", [], $error);
    }
    
    /**
     * debug_backtrace を文字列形式に変換します。
     *
     * @param array $trace debug_backtrace
     * @param boolean true : 引数記載有り／false : 引数記載無し（デフォルト）
     * @return string デバックバックトレース文字列
     */
    public static function traceToString(array $trace, bool $withArgs = false) : string
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

// エラーハンドラ登録
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    Log::errorHandle(['type' => $errno, 'message' => $errstr, 'file' => $errfile, 'line' => $errline]);
});

// シャットダウンハンドラ登録
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error) {
        Log::errorHandle($error);
    }
    Log::shutdown();
});
