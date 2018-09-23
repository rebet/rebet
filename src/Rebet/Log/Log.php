<?php
namespace Rebet\Log;

use Rebet\Config\Configurable;
use Rebet\DateTime\DateTime;
use Rebet\Pipeline\Pipeline;

/**
 * ロガー クラス
 *
 * 任意のハンドラとミドルウェアを組み合わせてログ出力を行います。
 *
 * パッケージで用意済みのハンドラ＆ミドルウェアには下記のものがあります。
 * ※これらのハンドラ及びミドルウェアは順次追加していきます。
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
     * ログミドルウェアパイプライン
     * @var Revet\Pipeline\Pipeline
     */
    private static $pipeline = null;

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
     *
     * @param callable|null $handler
     * @param array|null $middlewares
     * @return void
     */
    public static function init(?callable $handler = null, ?array $middlewares = null)
    {
        self::shutdown();
        self::$pipeline = (new Pipeline())
            ->through($middlewares ?? self::config('log_middlewares', false, []))
            ->then($handler ?? self::configInstantiate('log_handler'))
            ;
    }

    /**
     * ロガーをシャットダウンします。
     *
     * @return void
     */
    public static function shutdown()
    {
        if (self::$pipeline !== null) {
            Log::$pipeline->invoke('shutdown');
            Log::$pipeline->getDestination()->shutdown();
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
        if (self::$pipeline === null) {
            self::init();
        }
        self::$pipeline->send(new LogContext(DateTime::now(), $level, $message, $var, $error));
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
