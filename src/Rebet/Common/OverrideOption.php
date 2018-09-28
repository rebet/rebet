<?php
namespace Rebet\Common;

/**
 * オーバライドオプション クラス
 *
 * @todo Enum にするべきか？
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class OverrideOption
{
    /**
     * 置換オプション
     *
     * @var string
     */
    public const REPLACE = '!';

    /**
     * 前方追加オプション（連番配列のみ）
     *
     * @var string
     */
    public const PREPEND = '<';

    /**
     * 後方追加オプション（連番配列のみ）
     *
     * @var string
     */
    public const APEND = '>';
    
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
        foreach ([self::REPLACE, self::PREPEND, self::APEND] as $option) {
            if (Strings::endWith($key, $option)) {
                return [Strings::ratrim($key, $option), $option];
            }
        }

        return [$key, null];
    }
}
