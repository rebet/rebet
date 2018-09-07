<?php
namespace Rebet\Config;

use Rebet\Common\Util;

/**
 * アプリケーションコンフィグ クラス
 * 
 * アプリケーションの各種設定を管理するクラス
 * 
 * @todo 英語リソースを作成し、ライブラリデフォルトの設定を ja => en に変更する
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class App {
	use Configable;
	public static function defaultConfig(){
		return [
			'env'             => 'development',
			'locale'          => 'ja',
			'fallback_locale' => 'ja',
			'timezone'        => Util::evl(date_default_timezone_get(), 'UTC'),
		];
	}

	/**
	 * 現在のロケールを取得します。
	 * ※ App::config('locale') のファサードです。
	 */
	public static function getLocale() {
		return self::config('locale');
	}

	/**
	 * ロケールを設定します。
	 * 
	 * @param string $locale ロケール
	 */
	public static function setLocale(string $locale) : void {
		self::setConfig(['locale' => $locale]);
	}

	/**
	 * 特定のロケールであるか判定します。
	 * 
	 * @param ?string $locale ロケール
	 */
	public static function isLocale(?string $locale) : bool {
		return self::getLocale() === $locale;
	}

	/**
	 * 現在の環境を取得します。
	 * ※ App::config('env') のファサードです。
	 */
	public static function getEnv() {
		return self::config('env');
	}

	/**
	 * 現在の環境を設定します。
	 * 
	 * @param string $env 環境
	 */
	public static function setEnv(string $env) : void {
		self::setConfig(['env' => $env]);
	}

	/**
	 * 特定の環境であるか判定します。
	 * 
	 * @param ?string $env 環境
	 */
	public static function isEnv(?string $env) : bool {
		return self::getEnv() === $env;
	}

	/**
	 * 現在のタイムゾーンを取得します。
	 * ※ App::config('timezone') のファサードです。
	 */
	public static function getTimezone() {
		return self::config('timezone');
	}

	/**
	 * タイムゾーンを設定します。
	 * 
	 * @param string $timezone タイムゾーン
	 */
	public static function setTimezone(string $timezone) : void {
		self::setConfig(['timezone' => $timezone]);
	}

	/**
	 * 特定のタイムゾーンであるか判定します。
	 * 
	 * @param ?string $timezone タイムゾーン
	 */
	public static function isTimezone(?string $timezone) : bool {
		return self::getTimezone() === $timezone;
	}
}