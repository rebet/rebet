<?php
namespace Rebet\Log;

/**
 * ログコンテキスト クラス
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LogContext {

    /**
     * @var LogHandler
     */
    public $handler;

    /**
     * @var DateTime
     */
    public $now;

    /**
     * @var LogLevel
     */
    public $level;

    /**
     * @var mixed
     */
    public $message;

    /**
     * @var array
     */
    public $var;

    /**
     * @var array|\Throwable
     */
    public $error;

    /**
     * @var array
     */
    public $extra;

    /**
     * ログコンテキストを構築します。
     * 
     * @param LogHandler $handler ログハンドラ
     * @param DateTime $now 現在時刻
     * @param LogLevel $level ログレベル
     * @param mixed $message ログ内容
     * @param array $context コンテキスト（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @param array $extra エキストラ情報（デフォルト：[]）
     */
    public function __construct(LogHandler $handler, DateTime $now, LogLevel $level, $message, array $var = [], $error = null, array $extra = []) {
        $this->handler = $handler;
        $this->now     = $now;
        $this->level   = $level;
        $this->message = $message;
        $this->var     = $var;
        $this->error   = $error;
        $this->extra   = $extra;
    }
}
