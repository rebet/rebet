<?php
namespace Rebet\Routing;

use Rebet\Config\Configurable;

/**
 * Router Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Router
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'default_route'  => null,
            'fallback_route' => null,
        ];
    }

    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * ルートミドルウェアパイプライン
     * @var Rebet\Pipeline\Pipeline
     */
    private static $pipeline = null;
}
