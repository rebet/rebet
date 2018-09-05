<?php
namespace Rebet\Common;

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
	 */
	public static function unzip($zipPath, $destDir) {
		$zip = new ZipArchive();
		$res = $zip->open($zipPath);
		if ($res === true) {
			$zip->extractTo($destDir);
		    $zip->close();
		}
	}
	
	/**
	 * 対象のパスを ZIP 圧縮します。
	 * 
	 * @param  string   $sourcePath       圧縮対象ファイル or ディレクトリ
	 * @param  string   $outZipPath       圧縮後のZIPファイルパス
	 * @param  boolean  $includeTargetDir 指定ディレクトリをZIPアーカイブに含めるか否か（デフォルト：true[=含める]）
	 * @param  function $filter           格納データ取捨選択用フィルタ
	 *                                    ⇒ $path を引数に取り、 true を返すとそのパスを含み, false を返すとそのパスを除外する。
	 *                                    　 （デフォルト：null = function($path) { return true; }; = 全データ格納）
	 * @param  number   $outDirPermission ZIP格納ディレクトリ自動生成時のパーミッション（デフォルト：0775）
	 * @return void
	 */
	public static function zip($sourcePath, $outZipPath, $includeTargetDir=true, $filter=null, $outDirPermission=0775)
	{
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
		
		$z = new ZipArchive();
		$z->open($outZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
		if($includeTargetDir) {
			$z->addEmptyDir($dirName);
		}
		self::folderToZip($sourcePath, $z, strlen($includeTargetDir ? "$parentPath/" : "$parentPath/$dirName/"), $filter);
		$z->close();
	}
	
	/**
	 * ディレクトリを再帰的にZIP圧縮します。
	 * 
	 * @param  string   $folder
	 * @param  string   $zipFile
	 * @param  int      $exclusiveLength
	 * @param  function $filter
	 * @return void
	 */
	private static function folderToZip($folder, &$zipFile, $exclusiveLength, $filter) {
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