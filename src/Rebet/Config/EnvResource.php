<?php
namespace Rebet\Config;

use Rebet\Common\Arrays;

/**
 * 環境依存のリソースローダー クラス
 *
 * 現在の実行環境に応じて下記の手順でリソースファイルをロードします。
 *
 *  1. {$dir_path}/{$base_name}.{$suffix} ファイルを読み込み
 *  2. {$dir_path}/{$base_name}_{$env}.{$suffix} ファイルを読み込み
 *  3. 1 のデータを 2 のデータで Arrays::override
 *
 * なお、リソースのロードには Rebet\Config\Resource::load() が使用されるため、
 * 同クラスにローダーを追加することで自動的に本クラスでも対象のリソースを
 * 扱うことができるようになります。
 *
 * @see Rebet\Config\Resource
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class EnvResource
{
    /**
     * 指定のリソースをロードします。
     *
     * @param string $dir_path リソースファイルが存在するディレクトリパス
     * @param string $base_name リソースファイルベース名
     * @param string $env 環境名
     * @param string $suffix リソースサフィックス（デフォルト： php）
     * @param array $option ロードオプション（デフォルト： []）
     * @return リソースデータ
     * @throws \LogicException
     */
    public static function load(string $dir_path, string $base_name, string $env, string $suffix = 'php', array $option = []) : array
    {
        $base_resource_path = "{$dir_path}/{$base_name}.{$suffix}";
        $base_resource = Resource::load($suffix, $base_resource_path, $option);

        $env_resource_path = "{$dir_path}/{$base_name}_{$env}.{$suffix}";
        $env_resource = Resource::load($suffix, $env_resource_path, $option);

        if ($base_resource === null && $env_resource === null) {
            throw new \LogicException("Resource {$base_name} {$suffix} not found in {$dir_path}.");
        }
        
        return $env_resource === null ? $base_resource : Arrays::override($base_resource, $env_resource);
    }
}
