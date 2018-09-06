<?php
namespace Rebet\Config;

use Rebet\Common\Util;
use Rebet\Config\ConfigNotDefineException;

/**
 * コンフィグ クラス
 * 
 * 各種設定を統一的に扱うクラス。
 * 本クラスのコンフィグには以下の4段階の設定があり、
 * 
 * 　1. ライブラリコンフィグ
 * 　2. フレームワークコンフィグ
 * 　3. アプリケーションコンフィグ
 * 　4. ランタイムコンフィグ
 * 
 * 【優先度高 4 > 3 > 2 > 1 優先度低】 の優先度に従って設定を上書きすることができます。
 * なお、各設定は以下のように定義／動作します。
 * 
 * 　1. ライブラリコンフィグ
 * 　　　⇒ 各クラス定義にて Configable trait の実装
 * 　2. フレームワークコンフィグ
 * 　　　⇒ フレームワーク初期化処理にて Config::framework() で設定／上書き
 * 　3. アプリケーションコンフィグ
 * 　　　⇒ アプリケーション初期化処理にて Config::application() で設定／上書き
 * 　4. ランタイムコンフィグ
 * 　　　⇒ アプリケーション実行中に Config::runtime() で設定／上書き
 * 
 * @todo 環境切り分けの為の機能
 * @todo i18n 関連の考察
 * @todo 現在の設定（全て／セクション単位）を一覧するメソッドなどの実装
 * @todo 現在の設定（ライブラリ／フレームワーク／アプリケーション）を一覧するメソッドなどの実装
 * 
 * @see Rebet\Config\Configable
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
final class Config {

	private const LAYER_LIBRARY     = 'LIBRARY';
	private const LAYER_FRAMEWORK   = 'FRAMEWORK';
	private const LAYER_APPLICATION = 'APPLICATION';
	private const LAYER_RUNTIME     = 'RUNTIME';

	/**
	 * インスタンス化禁止
	 */
	private function __construct() {}

	/**
	 * コンフィグ設定
	 * 構造は下記の通り
	 * 
	 * CONFIG = [
	 *    Layer => [
	 *       Section => [
	 *           key => value
	 *       ]
	 *    ]
	 * ]
	 * 
	 * @var array
	 */
	private static $CONFIG = [
		self::LAYER_LIBRARY     => [],
		self::LAYER_FRAMEWORK   => [],
		self::LAYER_APPLICATION => [],
		self::LAYER_RUNTIME     => [],
	];

	/**
	 * コンフィグデータを全てクリアします
	 */
	public static function clear() : void {
		static::$CONFIG = [
			self::LAYER_LIBRARY     => [],
			self::LAYER_FRAMEWORK   => [],
			self::LAYER_APPLICATION => [],
			self::LAYER_RUNTIME     => [],
		];
	}

	
	/**
	 * 対象レイヤーのコンフィグを設定／上書きします。
	 * 本設定は array_merge による上書き設定となります。
	 */
	private static function override(string $layer, array $config) : void {
		static::$CONFIG[$layer] = \array_merge(static::$CONFIG[$layer], $config);
	}
	
	/**
	 * フレームワークコンフィグを設定します。
	 * 本設定は array_merge による上書き設定となります。
	 * 
	 * ex)
     * Config::framework([
     *     Dao::class => [
     *         'database' => 'rebet',
     *         'user' => 'rebet',
     *         'password' => 'password',
     *     ],
	 *     DateTime::class => [
	 *         'default_format' => 'Y/m/d H:i:s',
	 *     ],
	 *     'SectionName' => [
	 *          'key' => 'value',
	 *     ],
     * ]);
	 */
	public static function framework(array $config) : void {
		self::override(self::LAYER_FRAMEWORK, $config);
	}

	/**
	 * アプリケーションコンフィグを設定します。
	 * 本設定は array_merge による上書き設定となります。
	 * 
	 * ex)
     * Config::application([
     *     Dao::class => [
     *         'database' => 'rebet',
     *         'user' => 'rebet',
     *         'password' => 'password',
     *     ],
	 *     DateTime::class => [
	 *         'default_format' => 'Y/m/d H:i:s',
	 *     ],
	 *     'SectionName' => [
	 *          'key' => 'value',
	 *     ],
     * ]);
	 */
	public static function application(array $config) : void {
		self::override(self::LAYER_APPLICATION, $config);
	}

	/**
	 * ランタイムコンフィグを設定します。
	 * 本設定は array_merge による上書き設定となります。
	 * 
	 * ex)
     * Config::runtime([
     *     Dao::class => [
     *         'database' => 'rebet',
     *         'user' => 'rebet',
     *         'password' => 'password',
     *     ],
	 *     DateTime::class => [
	 *         'default_format' => 'Y/m/d H:i:s',
	 *     ],
	 *     'SectionName' => [
	 *          'key' => 'value',
	 *     ],
     * ]);
	 */
	public static function runtime(array $config) : void {
		self::override(self::LAYER_RUNTIME, $config);
	}
	
	/**
	 * 対象のコンフィグに指定の設定が定義されているかチェックします。
	 * 
	 * @param array $config チェック対象のコンフィグ
	 * @param string $section チェック対象のセクション
	 * @param int|string $key チェック対象のキー（.区切りで階層指定可）
	 */
	private static function isDefine(array $config, string $section, $key) {
		return isset($config[$section]) && Util::has($config[$section], $key) ;
	}

	/**
	 * コンフィグの設定値を取得します。
	 * 
	 * @param string $section セクション
	 * @param int|string $key 設定キー名（.区切りで階層指定可）
	 * @param bool $required 必須項目指定（デフォルト：true） … true指定時、設定値が blank だと例外を throw します
	 * @param ?mixed $default 必須項目指定が false で、値が未設定の場合にこの値が返ります。
	 * @return ?mixed 設定値
	 * @throw ConfigNotDefineException
	 */
	public static function get(string $section, $key, bool $required = true, $default = null) {
		foreach ([
			self::LAYER_RUNTIME     => 'Overwritten with blank at runtime layer.',
			self::LAYER_APPLICATION => 'Overwritten with blank at application layer.',
			self::LAYER_FRAMEWORK   => 'Please define at application layer.',
		] as $layer => $extra_message) {
			if(self::isDefine(static::$CONFIG[$layer], $section, $key)) {
				$value = Util::get(static::$CONFIG[$layer][$section], $key, $default);
				if($required && Util::isBlank($value)) {
					throw new ConfigNotDefineException("Required config {$section}#{$key} is blank. {$extra_message}}");
				}
				return $value;
			}
		}

		// ライブラリコンフィグ遅延ロード
		if(!isset(static::$CONFIG[self::LAYER_LIBRARY][$section])) {
			static::$CONFIG[self::LAYER_LIBRARY][$section] = method_exists($section, 'defaultConfig') ? $section::defaultConfig() : [] ;
		}
		
		// ライブラリコンフィグ
		$value = Util::get(static::$CONFIG[self::LAYER_LIBRARY][$section], $key, $default);
		if($required && Util::isBlank($value)) {
			if(self::isDefine(static::$CONFIG[self::LAYER_LIBRARY], $section, $key)) {
				throw new ConfigNotDefineException("Required config {$section}#{$key} is blank. Please define at application or framework layer.");
			}
			throw new ConfigNotDefineException("Required config {$section}#{$key} is not define. Please check config key name.");
		}
		return $value;
	}

	/**
	 * コンフィグの設定が定義されているかチェックします。
	 * 
	 * @param string $section セクション
	 * @param int|string $key 設定キー名（.区切りで階層指定）
	 * @return bool true: 定義済み, false: 未定義
	 */
	public static function has(string $section, $key) : bool {
		foreach ([self::LAYER_RUNTIME, self::LAYER_APPLICATION, self::LAYER_FRAMEWORK] as $layer) {
			if(self::isDefine(static::$CONFIG[$layer], $section, $key)) {
				return true;
			}
		}

		// ライブラリコンフィグ遅延ロード
		if(!isset(static::$CONFIG[self::LAYER_LIBRARY][$section])) {
			static::$CONFIG[self::LAYER_LIBRARY][$section] = method_exists($section, 'defaultConfig') ? $section::defaultConfig() : [] ;
		}

		// ライブラリコンフィグ
		return self::isDefine(static::$CONFIG[self::LAYER_LIBRARY], $section, $key);
	}
}