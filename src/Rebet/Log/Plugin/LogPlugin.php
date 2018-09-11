<?php
namespace Rebet\Log\Plugin;

use Rebet\DateTime\DateTime;
use Rebet\Log\Handler\LogHandler;

/**
 * ログプラグイン インターフェース
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface LogPlugin {
	/**
	 * ログプラグインを作成します。
	 * @return LogPlugin
	 */
	public static function create() : LogPlugin ;

	/**
	 * ログの事前処理します。
	 * 
	 * @param LogHandler $handler ログハンドラ
	 * @param DateTime $now 現在時刻
	 * @param int $level ログレベル
	 * @param array $extra エキストラ情報
	 * @return void
	 */
	public function prehook(LogHandler $handler, DateTime $now, int $level, array &$extra) : void ;

	/**
	 * ログの事後処理をします。
	 * 
	 * @param LogHandler $handler ログハンドラ
	 * @param DateTime $now 現在時刻
	 * @param int $level ログレベル
	 * @param string $formatted_log 整形済みログ
	 */
	public function posthook(LogHandler $handler, DateTime $now, int $level, string $formatted_log) : void ;

	/**
	 * プラグインのシャットダウン処理を実行します。
	 * 本メソッドは shotdown_handler でコールされます。
	 */
	public function shutdown() : void ;
}
