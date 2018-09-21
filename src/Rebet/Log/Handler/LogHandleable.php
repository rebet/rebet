<?php
namespace Rebet\Log\Handler;

use Rebet\Log\LogContext;

/**
 * ログハンドラ トレイト
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait LogHandleable
{
    /**
     * ログを処理します。
     *
     * @param LogContext $log ログコンテキスト
     * @return string|array|null 整形済みログデータ or null（ログ対象外時）
     */
    abstract public function handle(LogContext $log) ;

    /**
     * ログハンドラをシャットダウンします
     */
    abstract public function shutdown() : void ;

    /**
     * ログハンドラを Pipeline で処理できるようにします。
     *
     * @param LogContext $log ログコンテキスト
     * @return string|array|null 整形済みログデータ or null（ログ対象外時）
     */
    public function __invoke($log)
    {
        return $this->handle($log);
    }
}
