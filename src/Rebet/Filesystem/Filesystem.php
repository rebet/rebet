<?php
namespace Rebet\Filesystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\FilesystemInterface;
use Rebet\DateTime\DateTime;
use Rebet\Filesystem\Exception\FileNotFoundException;
use Rebet\Filesystem\Exception\FilesystemException;
use Rebet\Http\Response;

/**
 * Filesystem Interface
 *
 * Some fonctions interface are borrowed from Illuminate\Filesystem\FilesystemAdapter of laravel/framework ver 6.15.0 with some modifications.
 * NOTE:
 *    Function response() and download() are not defined this interface, if you want to do that please see Rebet\Http\Responder::file() and download() methods.
 *
 * @see https://github.com/laravel/framework/blob/v6.15.0/src/Illuminate/Filesystem/FilesystemAdapter.php
 * @see https://github.com/laravel/framework/blob/v6.15.0/LICENSE.md
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Filesystem
{
    /**
     * @var string 'public' visibility
     */
    const VISIBILITY_PUBLIC = 'public';

    /**
     * @var string 'private' visibility
     */
    const VISIBILITY_PRIVATE = 'private';

    /**
     * @var string 'wildcard' matching mode
     */
    const MATCHING_MODE_WILDCARD = 'wildcard';

    /**
     * @var string 'regex' matching mode
     */
    const MATCHING_MODE_REGEX = 'regex';

    /**
     * Create Filesystem using given adapter and config.
     *
     * @param AdapterInterface $adapter
     * @param array|Config|null $config (default: null)
     */
    public function __construct(AdapterInterface $adapter, $config = null);

    /**
     * Get the filesystem driver.
     *
     * @return FilesystemInterface
     */
    public function driver() : FilesystemInterface;

    /**
     * Get the filesystem adapter.
     *
     * @return AdapterInterface
     */
    public function adapter() : AdapterInterface;

    /**
     * Determine if a file exists.
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path) : bool;

    /**
     * It checks the given path is file or not
     *
     * @param string $path
     * @return boolean
     */
    public function isFile(string $path) : bool;

    /**
     * It checks the given path is directory or not
     *
     * @param string $path
     * @return boolean
     */
    public function isDirectory(string $path) : bool;

    /**
     * Get the full path for the file at the given "short" path.
     *
     * @param string $path (default: '/')
     * @return string
     */
    public function path(string $path = '/') : string;

    /**
     * Get the contents of a file.
     *
     * @param string $path
     * @return string
     * @throws FileNotFoundException
     */
    public function get(string $path) : string;

    /**
     * Write the contents of a file.
     * This method can be contains {.ext} placeholder in $path argument, like below
     *
     *  $path = $filesystem->put("users/{$user_id}/avatar{.ext}", $uploaded_file);
     *
     * If you can guess the extension (for exsample from mime type) then you can include ['.ext' => extension that was guessed] options for replacement of {.ext}.
     * Otherwise the {.ext} placeholder will be replaced by extension of given `SplFileInfo` contents.
     *
     * @param string $path can be contains {.ext} placeholder.
     * @param string|resource|\SplFileInfo|StreamInterface $contents string will be file contents
     * @param string|array $options (default: [])
     * @return string of saved path
     * @throws FilesystemException when can not save given contents
     */
    public function put(string $path, $contents, $options = []) : string;

    /**
     * Write the contents of a file.
     * This method can be contains {.ext} placeholder in $path argument, like below
     *
     *  $path = $filesystem->put("users/{$user_id}/avatar{.ext}", $uploaded_file);
     *
     * If you can guess the extension (for exsample from mime type) then you can include ['.ext' => extension that was guessed] options for replacement of {.ext}.
     * Otherwise the {.ext} placeholder will be replaced by extension of given `SplFileInfo` contents.
     *
     * @param string $path can be contains {.ext} placeholder.
     * @param string|resource|\SplFileInfo|StreamInterface $file string will be file path
     * @param string|array $options (default: [])
     * @return string of saved path
     * @throws FilesystemException when can not save given file
     */
    public function putFile(string $path, $file, $options = []) : string;

    /**
     * Get the visibility for the given path.
     *
     * @param string $path
     * @return string The visibility Filesystem::VISIBILITY_* (public|private)
     * @throws FilesystemException when can not get visibility
     */
    public function getVisibility(string $path) : string;

    /**
     * Set the visibility for the given path.
     *
     * @param string $path
     * @param string $visibility word of (public|private) Filesystem::VISIBILITY_*
     * @return self
     * @throws FileNotFoundException
     * @throws FilesystemException when can not set visibility
     */
    public function setVisibility(string $path, string $visibility) : self;

    /**
     * Prepend data to a file.
     *
     * @param string $path
     * @param string $data
     * @param string $separator (default: "\n")
     * @return self
     */
    public function prepend(string $path, string $data, string $separator = "\n") : self;

    /**
     * Append data to a file.
     *
     * @param string $path
     * @param string $data
     * @param string $separator (default: "\n")
     * @return self
     */
    public function append(string $path, string $data, string $separator = "\n") : self;

    /**
     * Delete the file and directory at a given path.
     *
     * @param string ...$paths
     * @return self
     * @throws FilesystemException when data can not delete
     */
    public function delete(string ...$paths) : self;

    /**
     * Delete all of the files and directories in the given path.
     *
     * @param string $directory (default: '/')
     * @return self
     * @throws FilesystemException when data can not delete
     */
    public function clean(string $directory = '/') : self;

    /**
     * Copy a file/directory to a new location.
     *
     * @param string $from
     * @param string $to
     * @param bool $replace or not when `to` path already exists (default: false)
     * @return self
     * @throws FileNotFoundException
     * @throws FilesystemException when can not copy
     */
    public function copy(string $from, string $to, bool $replace = false) : self;

    /**
     * Move/Rename a file/directory to a new location.
     *
     * @param string $from
     * @param string $to
     * @param bool $replace or not when `to` path already exists (default: false)
     * @return self
     * @throws FileNotFoundException
     * @throws FilesystemException when can not move
     */
    public function move(string $from, string $to, bool $replace = false) : self;

    /**
     * Get the file size of a given file.
     *
     * @param string $path
     * @return integer
     * @throws FileNotFoundException
     * @throws FilesystemException when can not get size
     */
    public function size(string $path) : int ;

    /**
     * Get a file's metadata.
     *
     * @param string $path
     * @return array
     * @throws FileNotFoundException
     * @throws FilesystemException when can not get metadata
     */
    public function metadata(string $path) : array;

    /**
     * Get the mime-type of a given file.
     *
     * @param string $path
     * @return string|null
     * @throws FileNotFoundException
     */
    public function mimeType(string $path) : ?string ;

    /**
     * Get the file's last modification time.
     *
     * @param string $path
     * @return DateTime
     * @throws FileNotFoundException
     * @throws FilesystemException when can not get last modified
     */
    public function lastModified(string $path) : DateTime;

    /**
     * Get the URL for the file at the given path.
     *
     * @param string $path
     * @return string|null
     * @throws FileNotFoundException when file not found or the file is not public.
     * @throws FilesystemException when the adapter does not support retrieving URLs.
     */
    public function url(string $path) : ?string;

    /**
     * Retrieves a read-stream for a path.
     *
     * @param string $path
     * @return resource|null
     * @throws FileNotFoundException
     */
    public function readStream(string $path);

    /**
     * Get an array of contents in a directory.
     *
     * @param string|null $directory
     * @param string|string[] $pattern (default: '*' that all matching pattern for 'wildcard' matching mode)
     * @param string|null $type 'file' or 'dir' (default: null for all type)
     * @param boolean $recursive (defalt: false)
     * @param string $matching_mode Filesystem::MATCHING_MODE_* 'wildcard' or 'regex' (default: Filesystem::MATCHING_MODE_WILDCARD)
     * @return array of matching file paths
     */
    public function ls(?string $directory = null, $pattern = '*', ?string $type = null, bool $recursive = false, string $matching_mode = Filesystem::MATCHING_MODE_WILDCARD) : array;

    /**
     * Get an array of files in a directory.
     *
     * @param string|null $directory
     * @param string|string[] $pattern (default: '*' that all matching pattern for 'wildcard' matching mode)
     * @param boolean $recursive (defalt: false)
     * @param string $matching_mode Filesystem::MATCHING_MODE_* 'wildcard' or 'regex' (default: Filesystem::MATCHING_MODE_WILDCARD)
     * @return array of matching file paths
     */
    public function files(?string $directory = null, $pattern = '*', bool $recursive = false, string $matching_mode = Filesystem::MATCHING_MODE_WILDCARD) : array;

    /**
     * Get an array of directories in a directory.
     *
     * @param string|null $directory
     * @param string|string[] $pattern (default: '*' that all matching pattern for 'wildcard' matching mode)
     * @param boolean $recursive (defalt: false)
     * @param string $matching_mode Filesystem::MATCHING_MODE_* 'wildcard' or 'regex' (default: Filesystem::MATCHING_MODE_WILDCARD)
     * @return array of matching file paths
     */
    public function directories(?string $directory = null, $pattern = '*', bool $recursive = false, string $matching_mode = Filesystem::MATCHING_MODE_WILDCARD) : array;

    /**
     * Create a directory
     *
     * @param string $path
     * @param array $config (default: [])
     * @return self
     */
    public function mkdir(string $path, array $config = []) : self;

    /**
     * Flush the Flysystem cache (if used cache adapter).
     *
     * @return self
     */
    public function flush() : self ;
}
