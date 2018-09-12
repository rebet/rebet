<?php
namespace Rebet\Log\Handler;

use Rebet\Config\Config;
use Rebet\DateTime\DateTime;
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
class FileHandler extends FormattableHandler {

    public static function defaultConfig() : array {
        return [
            'log_level'       => LogLevel::ERROR(),
            'log_formatter'   => \Rebet\Log\Formatter\DefaultFormatter::class,
            'log_file_path'   => null,
            'log_file_suffix' => '_Ym',
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
        $suffix   = self::config('log_file_suffix', false);
        $log_file = self::config('log_file_path').($suffix ? $now->format($suffix) : '');
        \error_log($formatted_log."\n", 3, $log_file);
    }

    /**
     * シャットダウン処理を行います。
     */
    public function shutdown() : void {
        // Nothing to do
    }
}

