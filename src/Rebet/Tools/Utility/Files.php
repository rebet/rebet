<?php

declare(strict_types=1);

namespace Rebet\Tools\Utility;

use Rebet\Tools\Utility\Exception\ZipArchiveException;

/**
 * Files Utility Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Files
{
    /**
     * No instantiation
     */
    private function __construct() {}

    /**
     * Delete the target directory including the subdirectory or all contains files and subdirectories.
     *
     * @param  string|null $dir
     * @param  bool        $remove_target_dir (default: true)
     * @return void
     */
    public static function removeDir(string|null $dir, bool $remove_target_dir = true): void
    {
        if ($dir === null || !file_exists($dir)) {
            return;
        }
        if ($handle = opendir($dir)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    if (is_dir("{$dir}/{$item}")) {
                        self::removeDir("{$dir}/{$item}");
                    } else {
                        unlink("{$dir}/{$item}");
                    }
                }
            }
            closedir($handle);
            if ($remove_target_dir) {
                rmdir($dir);
            }
        }
    }

    /**
     * Extract the target ZIP file.
     *
     * @param  string              $zip_path
     * @param  string              $dest_dir
     * @return void
     * @throws ZipArchiveException
     */
    public static function unzip(string $zip_path, string $dest_dir): void
    {
        $zip = new \ZipArchive();
        self::zipErrorCheck($zip->open($zip_path), "Open {$zip_path} failed.");
        $zip->extractTo($dest_dir);
        $zip->close();
    }

    /**
     * Convert ZipArchive error code to Exception.
     *
     * @param  int|bool            $code
     * @param  string              $message of error happend (default: 'ZipArchive error.')
     * @throws ZipArchiveException
     */
    private static function zipErrorCheck(bool|int $code, string $message = 'ZipArchive error.'): void
    {
        if ($code === true || $code === \ZipArchive::ER_OK) {
            return;
        }
        if ($code === false) {
            throw new ZipArchiveException($message);
        }
        $reason = match ($code) {
            \ZipArchive::ER_MULTIDISK   => 'Multi-disk zip archives not supported',
            \ZipArchive::ER_RENAME      => 'Renaming temporary file failed',
            \ZipArchive::ER_CLOSE       => 'Closing zip archive failed',
            \ZipArchive::ER_SEEK        => 'Seek error',
            \ZipArchive::ER_READ        => 'Read error',
            \ZipArchive::ER_WRITE       => 'Write error',
            \ZipArchive::ER_CRC         => 'CRC error',
            \ZipArchive::ER_ZIPCLOSED   => 'Containing zip archive was closed',
            \ZipArchive::ER_NOENT       => 'No such file',
            \ZipArchive::ER_EXISTS      => 'File already exists',
            \ZipArchive::ER_OPEN        => 'Can\'t open file',
            \ZipArchive::ER_TMPOPEN     => 'Failure to create temporary file',
            \ZipArchive::ER_ZLIB        => 'Zlib error',
            \ZipArchive::ER_MEMORY      => 'Malloc failure',
            \ZipArchive::ER_CHANGED     => 'Entry has been changed',
            \ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
            \ZipArchive::ER_EOF         => 'Premature EOF',
            \ZipArchive::ER_INVAL       => 'Invalid argument',
            \ZipArchive::ER_NOZIP       => 'Not a zip archive',
            \ZipArchive::ER_INTERNAL    => 'Internal error',
            \ZipArchive::ER_INCONS      => 'Zip archive inconsistent',
            \ZipArchive::ER_REMOVE      => 'Can\'t remove file',
            \ZipArchive::ER_DELETED     => 'Entry has been delete',
            default                     => 'Unknown reason',
        };

        throw (new ZipArchiveException("{$message} ({$reason})"))->code($code);
    }

    /**
     * Compress target path by ZIP.
     *
     * @param  string              $source_path        of target file or directory
     * @param  string              $out_zip_path
     * @param  boolean             $include_target_dir (default: true)
     * @param  \Closure            $filter             of zipped file selector (default: null = function($path) { return true; })
     *                                                 => Take $path as an argument, return true to include that path, and return false to exclude that path.
     * @param  int                 $out_dir_permission (default: 0775)
     * @return void
     * @throws ZipArchiveException
     */
    public static function zip(string $source_path, string $out_zip_path, bool $include_target_dir = true, \Closure|null $filter = null, int $out_dir_permission = 0o775): void
    {
        if (empty($filter)) {
            $filter = fn($path) => true;
        }

        $path_info   = pathInfo($source_path);
        $parent_path = $path_info['dirname'];
        $dir_name    = $path_info['basename'];

        $dest_dir = dirname($out_zip_path);
        if (!file_exists($dest_dir)) {
            mkdir($dest_dir, $out_dir_permission, true);
        }

        $z = new \ZipArchive();
        self::zipErrorCheck($z->open($out_zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE), "Open {$out_zip_path} failed.");
        if ($include_target_dir) {
            $z->addEmptyDir($dir_name);
        }
        self::folderToZip($source_path, $z, strlen($include_target_dir ? "{$parent_path}/" : "{$parent_path}/{$dir_name}/"), $filter);
        $z->close();
    }

    /**
     * ZIP compressed directories recursively.
     *
     * @param  string      $folder
     * @param  \ZipArchive $zip_file
     * @param  int         $exclusive_length
     * @param  \Closure    $filter
     * @return void
     */
    private static function folderToZip(string $folder, \ZipArchive &$zip_file, int $exclusive_length, \Closure $filter): void
    {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $file_path = "{$folder}/{$f}";
                if (!$filter($file_path)) {
                    continue;
                }

                // Remove prefix from file path before add to zip.
                $local_path = substr($file_path, $exclusive_length);
                if (is_file($file_path)) {
                    $zip_file->addFile($file_path, $local_path);
                } elseif (is_dir($file_path)) {
                    // Add sub-directory.
                    $zip_file->addEmptyDir($local_path);
                    self::folderToZip($file_path, $zip_file, $exclusive_length, $filter);
                }
            }
        }
        closedir($handle);
    }
}
