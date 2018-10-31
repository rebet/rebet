<?php
namespace Rebet\Log\Handler;

use Rebet\Config\Configurable;
use Rebet\Log\Formatter\LogFormatter;
use Rebet\Log\LogContext;

/**
 * フォーマット可能ログハンドラ 基底クラス
 * サブクラスの defaultConfig() で下記のコンフィグ設定を定義する必要があります。
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class FormattableHandler
{
    use Configurable, LogHandleable;

    /**
     * ログフォーマッタ
     * @var Rebet\Log\Formatter\LogFormatter
     */
    protected $formatter = null;

    /**
     * ログハンドラを構築します
     */
    public function __construct(?LogFormatter $formatter = null)
    {
        $this->formatter = $formatter ?? self::configInstantiate('log_formatter') ;
    }

    /**
     * ログデータを処理します。
     *
     * @param LogContext $log ログコンテキスト
     * @return string|array|null 整形済みログデータ or null（ログ対象外時）
     */
    public function handle(LogContext $log)
    {
        if ($log->level->lowerThan(self::config('log_level'))) {
            return null;
        }
        $formatted_log = $this->formatter->format($log);
        $this->report($log, $formatted_log);
        return $formatted_log;
    }

    /**
     * フォーマット済みのログデータを処理します。
     *
     * @param LogContext $log ログコンテキスト
     * @param string|array $formatted_log 整形済みログ
     */
    abstract protected function report(LogContext $log, $formatted_log) : void ;
}
