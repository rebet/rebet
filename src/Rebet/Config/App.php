<?php
namespace Rebet\Config;

use Dotenv\Dotenv;

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
            'env'             => Config::promise(function(){ return getenv('APP_ENV') ?: 'development' ;}),
            'locale'          => 'ja',
            'fallback_locale' => 'ja',
            'timezone'        => date_default_timezone_get() ?: 'UTC',
            'dotenv_validate' => function(Dotenv $dotenv){
                $dotenv->required(['APP_ENV'])->notEmpty();
            },
        ];
    }

    /**
     * Dotenv モジュールを用いて .env ファイルをロードします。
     * 本メソッドは composer の ../vendor/autoload.php の直後に呼ばれることが想定されています。
     * 
     * なお、必須環境変数などについては App::class セクションの 'dotenv_validate' で
     * クロージャにて再定義可能です。
     * 
     * ex)
     * Config::framework([
     *     App::class => [
     *         'dotenv_validate' => function(Dotenv $dotenv) {
     *             $parent = Config::parent(Layer::FRAMEWORK, App::class, 'dotenv_validate');
     *             if(is_callable($parent)) { $parent($dotenv); }
     *             $dotenv->required(['SEACRET_KEY', 'DB_USER', 'DB_***', ..., 'SMTP_HOST' ... etc])->notEmpty();
     *         }
     *     ]
     * ]);
     * 
     * @param string $path .env ファイルパス
     * @param string $filename .env ファイル名（デフォルト：.env）
     * @return Dotenv Dotenv オブジェクト
     */
    public static function loadDotenv(string $path, string $filename = '.env') : Dotenv {
        $dotenv = new Dotenv($path, $filename);
        $dotenv->load();
        $validator = self::config('dotenv_validate', false);
        if(is_callable($validator)) {
            $validator($dotenv);
        }
        return $dotenv;
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
     * @param string ...$locale ロケール
     */
    public static function locale(string ...$locale) : bool {
        return \in_array(self::getLocale(), $locale, true);
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
     * @param string ...$env 環境
     */
    public static function env(string ...$env) : bool {
        return \in_array(self::getEnv(), $env, true);
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
}