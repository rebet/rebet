<?php
namespace Rebet\Http\Bag;

use Rebet\Http\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag as SymfonyFileBag;

/**
 * File Bag Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class FileBag extends SymfonyFileBag
{
    /**
     * {@inheritDoc}
     *
     * @return UploadFile|UploadFile[]|null
     */
    protected function convertFileInformation($file)
    {
        $file = parent::convertFileInformation($file);
        if (is_array($file)) {
            return $file;
        }
        if ($file instanceof UploadedFile) {
            return $file;
        }

        return UploadedFile::valueOf($file);
    }
}
