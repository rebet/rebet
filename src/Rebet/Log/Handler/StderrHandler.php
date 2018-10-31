<?php
namespace Rebet\Log\Handler;

use Rebet\Log\LogContext;
use Rebet\Log\LogLevel;

/**
 * 標準エラー出力（STDERR）にログを出力するハンドラ クラス
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class StderrHandler extends FormattableHandler
{
    public static function defaultConfig() : array
    {
        return [
            'log_level'     => LogLevel::ERROR(),
            'log_formatter' => \Rebet\Log\Formatter\DefaultFormatter::class,
        ];
    }

    /**
     * ログハンドラを構築します
     */
    public function __construct(?LogFormatter $formatter = null)
    {
        parent::__construct($formatter);
    }

    /**
     * フォーマット済みのログデータを処理します。
     *
     * @param LogContext $log ログコンテキスト
     * @param string|array $formatted_log 整形済みログ
     */
    protected function report(LogContext $log, $formatted_log) : void
    {
        if (\is_array($formatted_log)) {
            $formatted_log = \print_r($formatted_log, true);
        }
        fwrite(STDERR, "{$formatted_log}\n");
    }

    /**
     * シャットダウン処理を行います。
     */
    public function terminate() : void
    {
        // Nothing to do
    }
}
