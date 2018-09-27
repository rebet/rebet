<?php
namespace Rebet\Config;

use Rebet\Common\Util;
use Rebet\IO\FileUtil;

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
class App
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'env'             => Config::promise(function () {
                return getenv('APP_ENV') ?: 'development' ;
            }),
            'interface'       => null,
            'root'            => null,
            'locale'          => 'ja',
            'fallback_locale' => 'ja',
            'timezone'        => date_default_timezone_get() ?: 'UTC',
        ];
    }

    /**
     * アプリケーションルートパスを取得します
     * ※ App::config('root') のファサードです。
     */
    public static function getRoot() : string
    {
        return self::config('root');
    }

    /**
     * アプリケーションルートパスを設定します
     *
     * @param string $app_root_path アプリケーションルートパス
     */
    public static function setRoot(string $app_root_path) : void
    {
        self::setConfig(['root' => FileUtil::normalizePath($app_root_path)]);
    }

    /**
     * アプリケーションルートからのルート相対パスを絶対パスに変換します。
     *
     * @param $root_relative_path アプリケーションルートからの相対パス
     * @return string 絶対パス
     */
    public static function path(string $root_relative_path) : string
    {
        return FileUtil::normalizePath(self::getRoot().'/'.$root_relative_path);
    }

    /**
     * 現在のロケールを取得します。
     * ※ App::config('locale') のファサードです。
     */
    public static function getLocale() : string
    {
        return self::config('locale');
    }

    /**
     * ロケールを設定します。
     *
     * @param string $locale ロケール
     */
    public static function setLocale(string $locale) : void
    {
        self::setConfig(['locale' => $locale]);
    }

    /**
     * 特定のロケールであるか判定します。
     *
     * @param string ...$locale ロケール
     */
    public static function localeIn(string ...$locale) : bool
    {
        return \in_array(self::getLocale(), $locale, true);
    }

    /**
     * 現在の環境を取得します。
     * ※ App::config('env') のファサードです。
     */
    public static function getEnv() : string
    {
        return self::config('env');
    }

    /**
     * 現在の環境を設定します。
     *
     * @param string $env 環境
     */
    public static function setEnv(string $env) : void
    {
        self::setConfig(['env' => $env]);
    }

    /**
     * 特定の環境であるか判定します。
     *
     * @param string ...$env 環境
     */
    public static function envIn(string ...$env) : bool
    {
        return \in_array(self::getEnv(), $env, true);
    }

    /**
     * 現在のインターフェース環境（web|api|console など）を取得します。
     * ※ App::config('interface') のファサードです。
     */
    public static function getInterface() : string
    {
        return self::config('interface');
    }

    /**
     * 現在のインターフェース環境（web|api|console など）を設定します。
     *
     * @param string $env 環境
     */
    public static function setInterface(string $interface) : void
    {
        self::setConfig(['interface' => $interface]);
    }

    /**
     * 特定のインターフェース環境（web|api|console など）であるか判定します。
     *
     * @param string ...$interface 環境
     */
    public static function interfaceIn(string ...$interface) : bool
    {
        return \in_array(self::getInterface(), $interface, true);
    }
    
    /**
     * 現在の実行環境を元に環境に則した値を返します。
     * $case のキー名には以下が指定でき、1 => 4 の優先度に従って値が取得されます。
     *
     *  1. interface@env
     *  2. interface
     *  3. env
     *  4. default
     *
     * @param array $case
     * @return mixed
     */
    public static function when(array $case)
    {
        $interface = static::getInterface();
        $env       = static::getEnv();
        return Util::get($case, "{$interface}@{$env}") ?? Util::get($case, $interface) ?? Util::get($case, $env) ?? Util::get($case, 'default');
    }

    /**
     * 現在のタイムゾーンを取得します。
     * ※ App::config('timezone') のファサードです。
     */
    public static function getTimezone()
    {
        return self::config('timezone');
    }

    /**
     * タイムゾーンを設定します。
     *
     * @param string $timezone タイムゾーン
     */
    public static function setTimezone(string $timezone) : void
    {
        self::setConfig(['timezone' => $timezone]);
    }
}
