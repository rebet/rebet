<?php
namespace Rebet\Log\Handler;

use Rebet\DateTime\DateTime;

/**
 * ログハンドラ インターフェース
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface LogHandler {
	/**
	 * ログハンドラを作成します。
	 * @return LogHandler
	 */
	public static function create() : LogHandler ;

	/**
	 * ログを処理します。
	 * 
	 * @param DateTime $now 現在時刻
	 * @param int $level ログレベル
	 * @param mixed $message ログ内容
	 * @param array $context コンテキスト（デフォルト：[]）
	 * @param \Throwable|array $error 例外 or error_get_last 形式配列（デフォルト：null）
	 * @param array $extra エキストラ情報（デフォルト：[]）
	 * @return string|array|null 整形済みログデータ or null（ログ対象外時）
	 */
	public function handle(DateTime $now, int $level, $message, array $context = [], $error = null, array $extra = []) ;

	/**
	 * ログハンドラをシャットダウンします
	 * 本メソッドは shotdown_handler でコールされます。
	 */
	public function shutdown() : void ;
}
