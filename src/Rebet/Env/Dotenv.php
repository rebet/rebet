<?php
namespace Rebet\Env;

use Dotenv\Dotenv as VlucasDotenv;

/**
 * 環境変数 クラス
 *
 * Dotenv モジュールを用いて .env ファイルをロードします。
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Dotenv extends VlucasDotenv
{
    /**
     * Dotenv モジュールを初期化して .env ファイルをロードします。
     * 本メソッドは composer の ../vendor/autoload.php の直後に呼ばれることが想定されています。
     *
     * @param string $path .env ファイルパス
     * @param string $filename .env ファイル名（デフォルト：.env）
     * @return Dotenv Dotenv オブジェクト
     */
    public static function init(string $path, string $filename = '.env') : self
    {
        $dotenv = new static($path, $filename);
        $dotenv->load();
        return $dotenv;
    }
}
