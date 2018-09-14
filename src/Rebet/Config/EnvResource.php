<?php
namespace Rebet\Config;

use Rebet\Common\ArrayUtil;
use Rebet\Config\Configable;

/**
 * 環境依存のリソースローダー クラス
 * 
 * 現在の実行環境に応じて下記の手順でリソースファイルをロードします。
 * 
 *  1. $dir_path/$base_name.$suffix ファイルを読み込み
 *  2. $dir_path/$base_name_{APP_ENV}.$suffix ファイルを読み込み
 *  3. 1 のデータを 2 のデータで ArrayUtil::override 
 * 
 * なお、現在対応しているリソース（拡張子）は下記の通りです。
 * 
 *  - php
 *    => 指定の php ファイルを require_once した結果を返します。
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
 * EnvResource::setLoader('yaml', function(string $path, array $option) {
 *     return Symfony\Component\Yaml\Yaml::parse(\file_get_contents($path));
 * })
 * 又は
 * Config::application([
 *     Rebet\Config\EnvResource::class => {
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
class EnvResource {
    use Configable;
    public static function defaultConfig() {
        return [
            'loader' => [
                'php'  => function(string $path, array $option) : array {
                    return require_once $path;
                },
                'json' => function(string $path, array $option) : array {
                    return \json_decode(\file_get_contents($path));
                },
                'ini'  => function(string $path, array $option) : array {
                    return \parse_ini_file($path, $option['process_sections'] ?? true, $option['scanner_mode'] ?? INI_SCANNER_TYPED);
                },
                'txt'  => function(string $path, array $option) : array {
                    return \explode($option['delimiter'] ?? "\n", \file_get_contents($path));
                },
            ]
        ];
    }

    /**
     * リソースローダーを登録します。
     * 
     * @param string $suffix リソースサフィックス
     * @param callable ローディングクロージャ
     * @return mixed
     */
    public static function setLoader(string $suffix, callable $loader) {
        self::setConfig(['loader' => [$suffix => $loader]]);
    }

    /**
     * 指定のリソースをロードします。
     * 
     * @param string $dir_path リソースファイルが存在するディレクトリパス
     * @param string $base_name リソースファイルベース名
     * @param string $suffix リソースサフィックス（デフォルト： php）
     * @param array $option ロードオプション（デフォルト： []）
     * @return リソースデータ
     * @throw \LogicException
     */
    public static function load(string $dir_path, string $base_name, string $suffix = 'php', array $option = []) : array {
        $loader = self::config("loader.{$suffix}");
        if(empty($loader) || !\is_callable($loader)) {
            throw new \LogicException("Unsupported file type [$suffix].");
        }

        $base_resource      = null;
        $base_resource_path = "{$dir_path}/{$base_name}.{$suffix}";
        if(\file_exists($base_resource_path)) {
            $base_resource = $loader($base_resource_path, $option);
        }

        $env_resource      = null;
        $env_resource_path = "{$dir_path}/{$env_name}_".App::getEnv().".{$suffix}";
        if(\file_exists($env_resource_path)) {
            $env_resource = $loader($env_resource_path, $option);
        }

        if($base_resource === null && $env_resource === null) {
            throw new \LogicException("Resource {$base_name} {$suffix} file not found in {$dir_path}.");
        }

        return $env_resource === null ? $base_resource : ArrayUtil::override($base_resource, $env_resource);
    }
}