<?php
namespace Rebet\File;

/**
 * ZipArchive関連例外 クラス
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ZipArchiveException extends \Exception
{
    public function __construct($message, $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
