<?php
namespace Rebet\Config;

/**
 * コンフィグ未定義例外 クラス
 * 
 * 必須指定された設定値が blank の場合に throw されます。
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ConfigNotDefineException extends \RuntimeException {
	public function __construct ($message, $code = null, $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
