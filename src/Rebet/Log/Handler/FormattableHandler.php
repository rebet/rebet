<?php
namespace Rebet\Log\Handler;

use Rebet\Config\Configable;
use Rebet\DateTime\DateTime;
use Rebet\Log\Formatter\LogFormatter;

/**
 * フォーマット可能ログハンドラ 基底クラス
 * サブクラスの defaultConfig() で下記のコンフィグ設定を定義する必要があります。
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class FormattableHandler implements LogHandler {
	use Configable;

	/**
	 * ログフォーマッタ
	 * @var Rebet\Log\Formatter\LogFormatter
	 */
	protected $formatter = null;

	/**
	 * ログハンドラを構築します
	 */
	public function __constract() {
		$formatter = self::config('log_formatter');
		$this->formatter = $formatter::create();
	}

	/**
	 * ログデータを処理します。
	 * 
	 * @param DateTime $now 現在時刻
	 * @param int $level ログレベル
	 * @param mixed $message ログ内容
	 * @param array $context コンテキスト（デフォルト：[]）
	 * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
	 * @param array $extra エキストラ情報（デフォルト：[]）
	 * @return string|array|null 整形済みログデータ or null（ログ対象外時）
	 */
	public function handle(DateTime $now, int $level, $message, array $context = [], $error = null, array $extra = []) {
		if(self::config('log_level') < $level) { return null; }
		$formatted_log = $this->$formatter->format($now, $level, $message, $context, $error, $extra);
		$this->report($now, $level, $formatted_log);
		return $formatted_log;
	}

	/**
	 * フォーマット済みのログデータを処理します。
	 * 
	 * @param DateTime $now 現在時刻
	 * @param int $level ログレベル
	 * @param string|array $formatted_log 整形済みログ
	 */
	abstract protected function report(DateTime $now, int $level, $formatted_log) : void ;
}
