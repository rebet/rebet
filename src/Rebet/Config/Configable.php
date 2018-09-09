<?php
namespace Rebet\Config;

/**
 * コンフィグ設定を利用する トレイト
 * 
 * 本トレイトを実装することで、対象クラス内にて以下の形でコンフィグを利用できます。
 * 
 *   self::config('key');
 *   //or static::config('key');
 * 
 * また、外部からは以下のようにコンフィグ設定にアクセスできます。
 * 
 *   ConfigableImplement::config('key');
 * 
 * なお、上記のアクセスは下記コードと同義です。
 * 
 *   Config::get(ConfigableImplement::class, 'key');
 * 
 * そのため、本トレイトにて実装されたデフォルトコンフィグ設定は以下のように上書可能となります。
 * 
 *   Config::application([
 *       ConfigableImplement::class => [
 *           'key' => 'new value'
 *       ]
 *   ]);
 * 
 * @see Rebet\Config\Config
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait Configable {
	
	/**
	 * デフォルトコンフィグ設定。
	 * 各トレイト導入クラスにてライブラリのデフォルト設定を定義して下さい。
	 * ここで返される設定は自動的にトレイト実装クラス名のセクションに分類されるため、
	 * セクションの指定は不要です。
	 * 
	 * ex) 
	 * // データベースに関連したクラスでの定義例
	 * public static function defaultConfig() {
	 *     return [
	 *         'driver' => 'mysql',
	 *         'host' => 'localhost',
	 *         'port' => 3306,
	 *         'database' => null,
	 *         'user' => null,
	 *     ];
	 * }
	 * 
	 * // 日時に関連したクラスでの定義例
	 * public static function defaultConfig() {
	 *     return [
	 *         'default_format'   => 'Y-m-d H:i:s',
	 *         'default_timezone' => Config::refer(App::class, 'timezone', Util::evl(date_default_timezone_get(), 'UTC')),
	 *     ];
	 * }
	 * 
	 * // Configable を実装したクラスを継承し、サブクラスで新しい設定を導入/上書する定義例
	 * public static function defaultConfig() {
	 *     return \array_merge(parent::defaultConfig(), [
	 *         'default_format' => 'M d, Y g:i A',
	 *         'new_key' => 'new_value',
	 *     ];
	 * }
	 * 
	 */
	abstract public static function defaultConfig() : array ;

	/**
	 * 自身のコンフィグ設定を取得します。
	 * 
	 * @param int|string $key 設定キー名（.区切りで階層指定）
	 * @param bool $required 必須項目指定（デフォルト：true） … true指定時、設定値が blank だと例外を throw します
	 * @param ?mixed $default 必須項目指定が false で、値が未設定の場合にこの値が返ります。
	 * @return ?mixed 設定値
	 * @throw ConfigNotDefineException
	 */
	public static function config($key, bool $required = true, $default = null) {
		return Config::get(static::class, $key, $required, $default);
	}

	/**
	 * 自身のコンフィグ設定を更新します。
	 * 本メソッドは ランタイムレイヤー のコンフィグ設定を追加します。
	 * 
	 * @param array $config コンフィグ設定
	 */
	protected static function setConfig(array $config) : void {
		Config::runtime([static::class => $config]);
	}
}