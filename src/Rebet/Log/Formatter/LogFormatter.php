<?php
namespace Rebet\Log\Formatter;

use Rebet\DateTime\DateTime;
use Rebet\Log\LogLevel;

/**
 * ログフォーマッタ インターフェース
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface LogFormatter {

	/**
	 * ログフォーマッタを作成します。
	 * 
	 * @return LogFormatter 
	 */
	public static function create() : LogFormatter ;

	/**
	 * ログをフォーマットします。
	 * 
	 * @param DateTime $now 現在時刻
	 * @param LogLevel $level ログレベル
	 * @param mixed $message ログ内容
	 * @param array $context コンテキスト（デフォルト：[]）
	 * @param \Throwable|array $error 例外 or error_get_last 形式の配列（デフォルト：null）
	 * @param array $extra エキストラ情報（デフォルト：[]）
	 * @return string|array 整形済みログ情報
	 */
	public function format(DateTime $now, LogLevel $level, $message, array $context = [], $error = null, array $extra = []) ;
}
