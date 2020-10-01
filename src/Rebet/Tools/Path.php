<?php
namespace Rebet\Tools;

use Rebet\Tools\Exception\LogicException;

/**
 * Path Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Path
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Normalize the given path.
     *
     * @param string $path
     * @return string
     */
    public static function normalize(string $path) : string
    {
        $protocol     = '';
        $drive        = '';
        $convert_path = \str_replace('\\', '/', $path);
        $is_relatable = true;
        if (Strings::contains($convert_path, '://')) {
            [$protocol, $convert_path] = \explode('://', $convert_path);
            $protocol                  = $protocol.'://';
            $is_relatable              = false;
        }
        if (Strings::contains($convert_path, ':/')) {
            [$drive, $convert_path] = \explode(':/', $convert_path);
            $drive                  = $drive.':/';
            $is_relatable           = false;
        }

        $parts     = explode('/', $convert_path);
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' === $part || '' === $part) {
                continue;
            }
            if ('..' !== $part) {
                $absolutes[] = $part;
                continue;
            }
            if (empty($absolutes) || end($absolutes) === '..') {
                if (!$is_relatable) {
                    throw new LogicException("Invalid path format: {$path}");
                }
                $absolutes[] = '..';
                continue;
            }
            \array_pop($absolutes);
        }

        $realpath = \implode('/', $absolutes);
        if ($is_relatable) {
            if (Strings::startsWith($convert_path, '/')) {
                if (!Strings::startsWith($realpath, '..')) {
                    $realpath = '/' . $realpath;
                }
            } else {
                if (empty($realpath)) {
                    $realpath = '.';
                }
            }
        }
        return $protocol.$drive.$realpath;
    }
}
