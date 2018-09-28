<?php
namespace Rebet\Common;

/**
 * 透過ドットアクセス インターフェース
 *
 * Util::get() による「ドット」表記アクセスにて、自身を透過（スキップ）して
 * 委譲先のオブジェクトへのアクセスを可能とするインターフェースです。
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface DotAccessDelegator
{
    /**
     * 移譲先のオブジェクトを取得します。
     */
    public function get();
}
