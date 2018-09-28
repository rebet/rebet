<?php
namespace Rebet\Config;

/**
 * ルート未定義例外 クラス
 *
 * 対象のルートが見つからない場合に throw されます。
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RouteNotFoundException extends \RuntimeException
{
    public function __construct($message, $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
