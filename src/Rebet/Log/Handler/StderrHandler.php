<?php
namespace Rebet\Log\Handler;

use Rebet\Config\Config;
use Rebet\DateTime\DateTime;
use Rebet\Log\LogLevel;

/**
 * 標準エラー出力（STDERR）にログを出力するハンドラ クラス
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class StderrHandler extends FormattableHandler {

    public static function defaultConfig() : array {
        return [
            'log_level'     => LogLevel::ERROR(),
            'log_formatter' => \Rebet\Log\Formatter\DefaultFormatter::class,
        ];
    }

    /**
     * ログハンドラを構築します。
     */
    public static function create() : LogHandler {
        return new static();
    }

    /**
     * フォーマット済みのログデータを処理します。
     * 
     * @param DateTime $now 現在時刻
     * @param LogLevel $level ログレベル
     * @param string|array $formatted_log 整形済みログ
     */
    protected function report(DateTime $now, LogLevel $level, $formatted_log) : void {
        if(\is_array($formatted_log)) {
            $formatted_log = \print_r($formatted_log, true);
        }
        fwrite(STDERR, "{$formatted_log}\n");
    }

    /**
     * シャットダウン処理を行います。
     */
    public function shutdown() : void {
        // Nothing to do
    }
}

