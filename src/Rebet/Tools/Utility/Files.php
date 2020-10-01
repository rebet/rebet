<?php
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
    private function __construct()
    {
    }

    /**
     * Delete the target directory including the subdirectory or all contains files and subdirectories.
     *
     * @param string|null $dir
     * @param bool $remove_target_dir (default: true)
     * @return void
     */
    public static function removeDir(?string $dir, bool $remove_target_dir = true) : void
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
     * @param string $zip_path
     * @param string $dest_dir
     * @return void
     * @throws Rebet\Tools\Utility\Exception\ZipArchiveException
     */
    public static function unzip(string $zip_path, string $dest_dir) : void
    {
        $zip = new \ZipArchive();
        self::zipErrorCheck($zip->open($zip_path), "Open {$zip_path} failed.");
        $zip->extractTo($dest_dir);
        $zip->close();
    }

    /**
     * Convert ZipArchive error code to Exception.
     *
     * @param int|bool $code
     * @param string|null $messsage of error happend (default: 'ZipArchive error.')
     * @throws Rebet\Tools\Utility\Exception\ZipArchiveException
     */
    private static function zipErrorCheck($code, string $message = 'ZipArchive error.') : void
    {
        if ($code === true) {
            return;
        }
        if ($code === false) {
            throw new ZipArchiveException($message);
        }
        switch ($code) {
            case \ZipArchive::ER_OK:          return;
            case \ZipArchive::ER_MULTIDISK:   $message = "{$message} (Multi-disk zip archives not supported)"; break;
            case \ZipArchive::ER_RENAME:      $message = "{$message} (Renaming temporary file failed)"; break;
            case \ZipArchive::ER_CLOSE:       $message = "{$message} (Closing zip archive failed)"; break;
            case \ZipArchive::ER_SEEK:        $message = "{$message} (Seek error)"; break;
            case \ZipArchive::ER_READ:        $message = "{$message} (Read error)"; break;
            case \ZipArchive::ER_WRITE:       $message = "{$message} (Write error)"; break;
            case \ZipArchive::ER_CRC:         $message = "{$message} (CRC error)"; break;
            case \ZipArchive::ER_ZIPCLOSED:   $message = "{$message} (Containing zip archive was closed)"; break;
            case \ZipArchive::ER_NOENT:       $message = "{$message} (No such file)"; break;
            case \ZipArchive::ER_EXISTS:      $message = "{$message} (File already exists)"; break;
            case \ZipArchive::ER_OPEN:        $message = "{$message} (Can't open file)"; break;
            case \ZipArchive::ER_TMPOPEN:     $message = "{$message} (Failure to create temporary file)"; break;
            case \ZipArchive::ER_ZLIB:        $message = "{$message} (Zlib error)"; break;
            case \ZipArchive::ER_MEMORY:      $message = "{$message} (Malloc failure)"; break;
            case \ZipArchive::ER_CHANGED:     $message = "{$message} (Entry has been changed)"; break;
            case \ZipArchive::ER_COMPNOTSUPP: $message = "{$message} (Compression method not supported)"; break;
            case \ZipArchive::ER_EOF:         $message = "{$message} (Premature EOF)"; break;
            case \ZipArchive::ER_INVAL:       $message = "{$message} (Invalid argument)"; break;
            case \ZipArchive::ER_NOZIP:       $message = "{$message} (Not a zip archive)"; break;
            case \ZipArchive::ER_INTERNAL:    $message = "{$message} (Internal error)"; break;
            case \ZipArchive::ER_INCONS:      $message = "{$message} (Zip archive inconsistent)"; break;
            case \ZipArchive::ER_REMOVE:      $message = "{$message} (Can't remove file)"; break;
            case \ZipArchive::ER_DELETED:     $message = "{$message} (Entry has been delete)"; break;
            default: $message                          = "{$message} (Unknown reason)"; break;
        }

        throw (new ZipArchiveException($message))->code($code);
    }

    /**
     * Compress target path by ZIP.
     *
     * @param string $source_path of target file or directory
     * @param string $out_zip_path
     * @param boolean $include_target_dir (default: true)
     * @param \Closure $filter of zipped file selector (default: null = function($path) { return true; })
     *                 => Take $path as an argument, return true to include that path, and return false to exclude that path.
     * @param int $out_dir_permission (default: 0775)
     * @return void
     * @throws Rebet\Tools\Utility\Exception\ZipArchiveException
     */
    public static function zip(string $source_path, string $out_zip_path, bool $include_target_dir = true, ?\Closure $filter = null, int $out_dir_permission = 0775) : void
    {
        if (empty($filter)) {
            $filter = function ($path) {
                return true;
            };
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
     * @param  string $folder
     * @param  \ZipArchive $zip_file
     * @param  int $exclusive_length
     * @param  \Closure $filter
     * @return void
     */
    private static function folderToZip(string $folder, \ZipArchive &$zip_file, int $exclusive_length, \Closure $filter)
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
