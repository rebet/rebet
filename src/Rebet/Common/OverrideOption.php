<?php
namespace Rebet\Common;

/**
 * オーバライドオプション クラス
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class OverrideOption
{
    /**
     * シーケンシャル配列追記オプション
     *
     * @var string
     */
    public const ARRAY_APPEND = '+';

    /**
     * 連想配列上書オプション
     *
     * @var string
     */
    public const MAP_NO_MERGE = '!';

    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * 指定のキー文字列を純粋キー名とオプションに分割します。
     *
     * @param string $key
     * @return array [string $key, string|null $option]
     */
    public static function split(string $key) : array
    {
        foreach ([self::MAP_NO_MERGE, self::ARRAY_APPEND] as $option) {
            if (StringUtil::endWith($key, $option)) {
                return [StringUtil::ratrim($key, $option), $option];
            }
        }

        return [$key, null];
    }
}
