<?php
namespace Rebet\Config;

/**
 * コンフィグ関連クラスのレイヤー名を定義する クラス
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
final class Layer {

    /**
     * インスタンス化禁止
     */
    private function __construct() {}

    /**
     * ライブラリレイヤー
     * @var string 'library'
     */
    public const LIBRARY = 'library';

    /**
     * フレームワークレイヤー
     * @var string 'framework'
     */
    public const FRAMEWORK = 'framework';

    /**
     * アプリケーションレイヤー
     * @var string 'application'
     */
    public const APPLICATION = 'application';

    /**
     * ランタイムレイヤー
     * @var string 'runtime'
     */
    public const RUNTIME = 'runtime';
}