<?php
namespace Rebet\Env;

use Dotenv\Dotenv;

/**
 * 環境変数 クラス
 *
 * Dotenv モジュールを用いて .env ファイルをロードします。
 *
 * @todo 本クラスは無くても良いのでは？ 直接 Dotenv を使用すべきか？
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Env
{
    /**
     * Dotenv モジュールを用いて .env ファイルをロードします。
     * 本メソッドは composer の ../vendor/autoload.php の直後に呼ばれることが想定されています。
     *
     * @param string $path .env ファイルパス
     * @param string $filename .env ファイル名（デフォルト：.env）
     * @return Dotenv Dotenv オブジェクト
     */
    public static function load(string $path, string $filename = '.env') : Dotenv
    {
        $dotenv = new Dotenv($path, $filename);
        $dotenv->load();
        return $dotenv;
    }
}
