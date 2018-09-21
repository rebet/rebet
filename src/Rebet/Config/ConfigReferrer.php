<?php
namespace Rebet\Config;

use Rebet\Common\TransparentlyDotAccessible;

/**
 * コンフィグ参照 クラス
 *
 * 他のセクションのコンフィグ設定を共有する場合に利用するクラスとなります。
 * なお、参照は片方向参照となります。
 * ※本オブジェクトは Config::refer() ファサードを利用して構築できます。
 *
 * @see Rebet\Config\Config::refer()
 *
 * @todo 循環参照の検知＆例外 throw
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ConfigReferrer implements TransparentlyDotAccessible
{
    
    /**
     * 参照先セクション
     */
    private $section = null;

    /**
     * 参照先キー
     */
    private $key = null;

    /**
     * 参照先がブランクの場合はのデフォルト値
     */
    private $default = null;

    /**
     * コンフィグ設定参照クラスを構築します。
     * @param string $section 参照先セクション
     * @param int|string $key 参照先キー
     * @param mixed 参照値
     */
    public function __construct(string $section, $key, $default = null)
    {
        $this->section = $section;
        $this->key     = $key;
        $this->default = $default;
    }

    /**
     * 参照先の現在の設定値を取得します。
     */
    public function get()
    {
        return Config::get($this->section, $this->key, false, $this->default);
    }
}
