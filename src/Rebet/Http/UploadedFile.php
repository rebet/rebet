<?php
namespace Rebet\Http;

use Symfony\Component\HttpFoundation\UploadedFile as SymfonyUploadedFile;

/**
 * Uploa dedFile Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class UploadedFile extends SymfonyUploadedFile
{
    /**
     * Convert to UploadedFile from given value.
     *
     * @param mixed $value
     * @return self|null
     */
    public static function valueOf($value) : ?self
    {
        switch (true) {
            case $value === null:
                return null;
            case $value instanceof static:
                return $value;
            case $value instanceof SymfonyUploadedFile:
                return new static(
                    $value->getPathname(),
                    $value->getClientOriginalName(),
                    $value->getClientMimeType(),
                    $value->getError()
                );
            case is_array($value):
                return new static(
                    $value['tmp_name'],
                    $value['name'],
                    $value['type'],
                    $value['error']
                );
        }
        return null;
    }
}
