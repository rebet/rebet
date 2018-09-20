<?php
namespace Rebet\IO;

use Rebet\Common\StringUtil;

/**
 * ファイル関連 ユーティリティ クラス
 * 
 * ファイル関連の簡便なユーティリティメソッドを集めたクラスです。
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class FileUtil {
    
    /**
     * インスタンス化禁止
     */
    private function __construct() {}

    public static function normalizePath(string $path) {
        $protocol     = '';
        $drive        = '';
        $convert_path = \str_replace('\\', '/', $path);
        $is_relatable = true;
        if(StringUtil::contains($convert_path, '://')) {
            [$protocol, $convert_path] = \explode('://', $convert_path);
            $protocol     = $protocol.'://';
            $is_relatable = false;
        }
        if(StringUtil::contains($convert_path, ':/')) {
            [$drive, $convert_path] = \explode(':/', $convert_path);
            $drive        = $drive.':/';
            $is_relatable = false;
        }

        $parts = explode('/', $convert_path);
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' === $part || '' === $part) { continue; }
            if ('..' !== $part) {
                $absolutes[] = $part;
                continue;
            }
            if(empty($absolutes) || end($absolutes) === '..') {
                if(!$is_relatable) {
                    throw new \LogicException("Invalid path format: {$path}");
                }
                $absolutes[] = '..';
                continue;
            }
            \array_pop($absolutes);
        }
        
        $realpath = \implode('/', $absolutes);
        if($is_relatable) {
            if(StringUtil::startWith($convert_path, '/')) {
                if(!StringUtil::startWith($realpath, '..')) {
                    $realpath = '/' . $realpath;
                }
            } else {
                if(empty($realpath)) {
                    $realpath = '.';
                }
            }
        }
        return $protocol.$drive.$realpath;
    }

    /**
     * 対象のディレクトリを サブディレクトリを含め 削除します。
     * 
     * @param  string $dir 削除対象ディレクトリパス
     * @return void
     */
    public static function removeDir(string $dir) : void {
        if(!file_exists($dir)) { return; }
        if ($handle = opendir("$dir")) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    if (is_dir("$dir/$item")) {
                        self::removeDir("$dir/$item");
                    } else {
                        unlink("$dir/$item");
                    }
                }
            }
            closedir($handle);
            rmdir($dir);
        }
    }

    /**
     * 対象の ZIP ファイルを展開します。
     * 
     * @param  string $zipPath ZIPファイルパス
     * @param  string $destDir 展開先ディレクトリパス
     * @return void
     * @throws Rebet\IO\ZipArchiveException
     */
    public static function unzip(string $zipPath, string $destDir) : void {
        $zip = new \ZipArchive();
        self::zipErrorCheck($zip->open($zipPath), "Open {$zipPath} failed.");
        $zip->extractTo($destDir);
        $zip->close();
    }
    
    /**
     * ZipArchive の エラーコード を Exception に変換します。
     * 
     * @param int|bool $code 成否及びエラーコード
     * @param ?string $messsage エラー発生時のメッセージ（デフォルト： 'ZipArchive error.'）
     * @throws Rebet\IO\ZipArchiveException
     */
    private static function zipErrorCheck($code, string $message = 'ZipArchive error.') : void {
        if($code === true) { return; }
        if($code === false) { throw new ZipArchiveException($message); }
        switch($code) {
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
            default: $message = "{$message} (Unknown reason)"; break;
        }

        throw new ZipArchiveException($message, $code);
    }

    /**
     * 対象のパスを ZIP 圧縮します。
     * 
     * @param  string   $sourcePath       圧縮対象ファイル or ディレクトリ
     * @param  string   $outZipPath       圧縮後のZIPファイルパス
     * @param  boolean  $includeTargetDir 指定ディレクトリをZIPアーカイブに含めるか否か（デフォルト：true[=含める]）
     * @param  \Closure $filter           格納データ取捨選択用フィルタ
     *                                    ⇒ $path を引数に取り、 true を返すとそのパスを含み, false を返すとそのパスを除外する。
     *                                    　 （デフォルト：null = function($path) { return true; }; = 全データ格納）
     * @param  number   $outDirPermission ZIP格納ディレクトリ自動生成時のパーミッション（デフォルト：0775）
     * @return void
     * @throws Rebet\IO\ZipArchiveException
     */
    public static function zip(string $sourcePath, string $outZipPath, bool $includeTargetDir = true, ?\Closure $filter = null, int $outDirPermission = 0775) : void {
        if(empty($filter)) {
            $filter = function($path) { return true; };
        }
        
        $pathInfo = pathInfo($sourcePath);
        $parentPath = $pathInfo['dirname'];
        $dirName = $pathInfo['basename'];
        
        $destDir = dirname($outZipPath);
        if(!file_exists($destDir)) {
            mkdir($destDir, $outDirPermission, true);
        }
        
        $z = new \ZipArchive();
        self::zipErrorCheck($z->open($outZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE), "Open {$outZipPath} failed.");
        if($includeTargetDir) {
            $z->addEmptyDir($dirName);
        }
        self::folderToZip($sourcePath, $z, strlen($includeTargetDir ? "$parentPath/" : "$parentPath/$dirName/"), $filter);
        $z->close();
    }
    
    /**
     * ディレクトリを再帰的にZIP圧縮します。
     * 
     * @param  string $folder
     * @param  \ZipArchive $zipFile
     * @param  int $exclusiveLength
     * @param  \Closure $filter
     * @return void
     */
    private static function folderToZip(string $folder, \ZipArchive &$zipFile, int $exclusiveLength, \Closure $filter) {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder/$f";
                if(!$filter($filePath)) { continue; }
                
                // Remove prefix from file path before add to zip.
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory.
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength, $filter);
                }
            }
        }
        closedir($handle);
    }
}