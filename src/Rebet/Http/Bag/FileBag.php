<?php
declare(strict_types=1);

namespace Rebet\Http\Bag;

use Rebet\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
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
     * @param array<mixed>|SymfonyUploadedFile $file
     * @return UploadedFile|UploadedFile[]|null
     */
    protected function convertFileInformation(array|SymfonyUploadedFile $file) : array|SymfonyUploadedFile|null
    {
        $file = parent::convertFileInformation($file);
        if (is_array($file)) {
            return array_map(function ($f) {
                return $f instanceof UploadedFile ? $f : UploadedFile::valueOf($f);
            }, $file);
        }
        if ($file instanceof UploadedFile) {
            return $file;
        }

        return UploadedFile::valueOf($file);
    }
}
