<?php
namespace Rebet\Log;

use Rebet\DateTime\DateTIme;

/**
 * ログコンテキスト クラス
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Logvar
{
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
     * @var array|\Throwable|null
     */
    public $error;

    /**
     * @var array
     */
    public $extra;

    /**
     * ログコンテキストを構築します。
     *
     * @param DateTime $now 現在時刻
     * @param LogLevel $level ログレベル
     * @param mixed $message ログ内容
     * @param array $var 変数（デフォルト：[]）
     * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
     * @param array $extra エキストラ情報（デフォルト：[]）
     */
    public function __construct(DateTime $now, LogLevel $level, $message, array $var = [], $error = null, array $extra = [])
    {
        $this->now     = $now;
        $this->level   = $level;
        $this->message = $message;
        $this->var     = $var;
        $this->error   = $error;
        $this->extra   = $extra;
    }
}
