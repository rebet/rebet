<?php
namespace Rebet\Log\Handler;

use Rebet\Log\LogContext;
use Rebet\Log\LogLevel;

/**
 * ファイル出力ログハンドラ クラス
 *
 * error_log() を使用した簡便なファイル出力ログを行います。
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class FileHandler extends FormattableHandler
{
    public static function defaultConfig() : array
    {
        return [
            'log_level'       => LogLevel::ERROR(),
            'log_formatter'   => \Rebet\Log\Formatter\DefaultFormatter::class,
            'log_file_path'   => null,
            'log_file_suffix' => '_Ym',
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
        $suffix   = self::config('log_file_suffix', false);
        $log_file = self::config('log_file_path').($suffix ? $log->now->format($suffix) : '');
        \error_log($formatted_log."\n", 3, $log_file);
    }

    /**
     * シャットダウン処理を行います。
     */
    public function terminate() : void
    {
        // Nothing to do
    }
}
