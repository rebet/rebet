<?php
namespace Rebet\Log\Formatter;

use Rebet\DateTime\DateTime;
use Rebet\Log\LogContext;

/**
 * ログフォーマッタ インターフェース
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface LogFormatter
{
    /**
     * ログをフォーマットします。
     *
     * @param LogContext $log ログコンテキスト
     * @return string|array 整形済みログ情報
     */
    public function format(LogContext $log) ;
}
