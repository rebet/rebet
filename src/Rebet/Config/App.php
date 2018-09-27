<?php
namespace Rebet\Config;

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
            'surface'         => null,
            'env'             => Config::promise(function () {
                return getenv('APP_ENV') ?: 'development' ;
            }),
            'entry_point'     => null,
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
     * 現在の流入経路口（web|api|console など）を取得します。
     * ※ App::config('surface') のファサードです。
     */
    public static function getSurface() : string
    {
        return self::config('surface');
    }

    /**
     * 現在の流入経路口（web|api|console など）を設定します。
     *
     * @param string $surface 流入経路口
     */
    public static function setSurface(string $surface) : void
    {
        self::setConfig(['surface' => $surface]);
    }

    /**
     * 特定の流入経路口（web|api|console など）であるか判定します。
     *
     * @param string ...$surface 流入経路口
     */
    public static function surfaceIn(string ...$surface) : bool
    {
        return \in_array(self::getSurface(), $surface, true);
    }
    
    /**
     * 現在の実行環境を元に環境に則した値を返します。
     * $case のキー名には以下が指定でき、1 => 4 の優先度に従って値が取得されます。
     *
     *  1. surface@env
     *  2. surface
     *  3. env
     *  4. default
     *
     * @param array $case
     * @return mixed
     */
    public static function when(array $case)
    {
        $surface = static::getSurface();
        $env     = static::getEnv();
        return $case["{$surface}@{$env}"] ?? $case[$surface] ?? $case[$env] ?? $case['default'];
    }

    /**
     * 現在のエントリポイント名を取得します。
     * ※ App::config('entry_point') のファサードです。
     */
    public static function getEntryPoint() : string
    {
        return self::config('entry_point');
    }

    /**
     * 現在のエントリポイント名を設定します。
     *
     * @param string $entry_point エントリポイント名
     */
    public static function setEntryPoint(string $entry_point) : void
    {
        self::setConfig(['entry_point' => $entry_point]);
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
