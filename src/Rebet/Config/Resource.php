<?php
namespace Rebet\Config;

use Rebet\Common\ArrayUtil;
use Rebet\Config\Configurable;

/**
 * リソースローダー クラス
 * 
 * 現在対応しているリソース（拡張子）は下記の通りです。
 * 
 *  - php
 *    => 指定の php ファイルを require した結果を返します。
 *    => オプション：なし
 * 
 *  - json
 *    => 指定の json ファイルを json_decode した結果を返します。
 *    => オプション：なし
 * 
 *  - ini
 *    => 指定の ini ファイルを parse_ini_file した結果を返します。
 *    => オプション：
 *         process_sections => bool          （デフォルト： true）
 *         scanner_mode     => INI_SCANNER_* （デフォルト： INI_SCANNER_TYPED）
 * 
 *  - txt
 *    => 指定の txt ファイルを explode した結果を返します。
 *    => オプション：
 *         delimiter => string （デフォルト： \n）
 * 
 * 上記のリソースローダーの挙動は Config 設定で loader クロージャを上書きすることで変更可能です。
 * また、新規のリソースも Config 設定にて loader クロージャを定義することで追加できます。
 * 
 * ex) 
 * Resource::setLoader('yaml', function(string $path, array $option) {
 *     return Symfony\Component\Yaml\Yaml::parse(\file_get_contents($path));
 * })
 * 又は
 * Config::application([
 *     Rebet\Config\Resource::class => {
 *         'loader' => [
 *             'yaml' => function(string $path, array $option) : array {
 *                  return Symfony\Component\Yaml\Yaml::parse(\file_get_contents($path));
 *             }
 *         ]
 *     }
 * ]);
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Resource {
    use Configurable;
    public static function defaultConfig() {
        return [
            'loader' => [
                'php'  => function(string $path, array $option) {
                    if(!\file_exists($path)) { return null; }
                    return require $path;
                },
                'json' => function(string $path, array $option) {
                    if(!\file_exists($path)) { return null; }
                    return \json_decode(\file_get_contents($path), true);
                },
                'ini'  => function(string $path, array $option) {
                    if(!\file_exists($path)) { return null; }
                    return \parse_ini_file($path, $option['process_sections'] ?? true, $option['scanner_mode'] ?? INI_SCANNER_TYPED);
                },
                'txt'  => function(string $path, array $option) {
                    if(!\file_exists($path)) { return null; }
                    return \explode($option['delimiter'] ?? "\n", \file_get_contents($path));
                },
            ]
        ];
    }

    /**
     * リソースローダーを登録します。
     * 
     * @param string $suffix リソースサフィックス
     * @param \Closure ローディングクロージャ
     * @return mixed
     */
    public static function setLoader(string $suffix, \Closure $loader) {
        self::setConfig(['loader' => [$suffix => $loader]]);
    }

    /**
     * 指定のリソースをロードします。
     * 
     * @param string $type リソースタイプ（=拡張子）
     * @param string $path リソースファイルパス
     * @param array $option ロードオプション（デフォルト： []）
     * @return リソースデータ
     * @throws \LogicException
     */
    public static function load(string $type, string $path, array $option = []) {
        $loader = self::config("loader.{$type}", false);
        if(empty($loader) || !\is_callable($loader)) {
            throw new \LogicException("Unsupported file type [$type]. Please set loader to Rebet\Config\Resource class.");
        }
        return $loader($path, $option);
    }
}