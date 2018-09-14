<?php
namespace Rebet\Env;

use Rebet\Config\Configable;
use Dotenv\Dotenv;

/**
 * 環境変数 クラス
 * 
 * 環境変数を管理するクラス
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Env {
    use Configable;
    public static function defaultConfig(){
        return [
            'dotenv_validate' => []
        ];
    }

    /**
     * Dotenv モジュールを用いて .env ファイルをロードします。
     * 本メソッドは composer の ../vendor/autoload.php の直後に呼ばれることが想定されています。
     * 
     * なお、必須環境変数などについては App::class セクションの 'dotenv_validate' で再定義可能です。
     * ※単純な required + notEmpty なチェックのみで良い場合は配列でENV名を列挙して下さい
     * ※なお、より複雑なチェックを行う場合は function(Dotenv $dotenv) {} 形式のクロージャを指定することもできます。
     * 
     * ex)
     * Config::framework([
     *     Env::class => [
     *         'dotenv_validate' => ['APP_ENV', 'SEACRET_KEY', 'DB_***', ..., 'SMTP_HOST' ... etc],
     *     ]
     * ]);
     * 
     * Config::framework([
     *     Env::class => [
     *         'dotenv_validate' => function(Dotenv $dotenv) {
     *             $dotenv->required(['APP_ENV', 'SEACRET_KEY', 'DB_***', ..., 'SMTP_HOST' ... etc])->notEmpty();
     *             $dotenv->required(['DB_PORT'])->isInteger();
     *         }
     *     ]
     * ]);
     * 
     * @param string $path .env ファイルパス
     * @param string $filename .env ファイル名（デフォルト：.env）
     * @return Dotenv Dotenv オブジェクト
     */
    public static function load(string $path, string $filename = '.env') : Dotenv {
        $dotenv = new Dotenv($path, $filename);
        $dotenv->load();
        $validator = self::config('dotenv_validate', false);
        if(is_array($validator) && !empty($validator)) {
            $dotenv->required($validator)->notEmpty();
        } else if(is_callable($validator)) {
            $validator($dotenv);
        }
        return $dotenv;
    }
}