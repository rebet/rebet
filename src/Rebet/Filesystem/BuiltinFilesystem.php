<?php
namespace Rebet\Filesystem;

use League\Flysystem\Adapter\AbstractFtpAdapter;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Config;
use League\Flysystem\FileNotFoundException as FlysystemFileNotFoundException;
use League\Flysystem\Filesystem as FlysystemFilesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Util\MimeType;
use Psr\Http\Message\StreamInterface;
use Rebet\Common\Path;
use Rebet\Common\Strings;
use Rebet\Config\Configurable;
use Rebet\DateTime\DateTime;
use Rebet\Filesystem\Exception\FileNotFoundException;
use Rebet\Filesystem\Exception\FilesystemException;
use Rebet\Stream\Stream;
use Symfony\Component\Mime\MimeTypes;

/**
 * Builtin Filesystem Class
 *
 * Some fonctions implementation are borrowed from Illuminate\Filesystem\FilesystemAdapter of laravel/framework ver 6.15.0 with some modifications.
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
class BuiltinFilesystem implements Filesystem
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'driver'  => FlysystemFilesystem::class,
        ];
    }

    /**
     * @var FilesystemInterface
     */
    protected $driver;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * {@inheritDoc}
     */
    public function __construct(AdapterInterface $adapter, $config = null)
    {
        $driver        = static::config('driver');
        $this->driver  = new $driver($adapter, $config);
        $this->adapter = $adapter;
    }

    /**
     * {@inheritDoc}
     */
    public function driver() : FilesystemInterface
    {
        return $this->driver;
    }

    /**
     * {@inheritDoc}
     */
    public function adapter() : AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $path) : bool
    {
        return $this->driver->has($path);
    }

    /**
     * {@inheritDoc}
     */
    public function path(string $path = '/') : string
    {
        return Path::normalize($this->driver->getAdapter()->getPathPrefix().$path);
    }

    /**
     * Convert Flysystem exception to Rebet exception.
     *
     * @param \Exception $e
     * @return FilesystemException
     */
    protected function convertException(\Exception $e) : FilesystemException
    {
        switch (true) {
            case $e instanceof FilesystemException:            return $e;
            case $e instanceof FlysystemFileNotFoundException: return FileNotFoundException::from($e);
        }
        return new FilesystemException($e->getMessage(), $e);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $path) : string
    {
        try {
            return $this->driver->read($path);
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $path, $contents, $options = []) : Filesystem
    {
        $options = is_string($options) ? ['visibility' => $options] : (array) $options ;
        $stream  = null;
        switch (true) {
            case $contents instanceof \SplFileInfo:    $stream = fopen($contents->getRealPath(), 'r'); break;
            case $contents instanceof StreamInterface: $stream = $contents->detach();                  break;
            default:                                   $stream = $contents;
        }

        $put_method = is_resource($stream) ? 'putStream' : 'put' ;
        if (!$this->driver->{$put_method}($path, $stream, $options)) {
            throw new FilesystemException("Can not save contents to `{$path}`.");
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function putFile(string $path, $file, $options = []) : Filesystem
    {
        return $this->put($path, is_string($file) ? fopen($file, 'r') : $file, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function getVisibility(string $path) : string
    {
        if ($visibility = $this->driver->getVisibility($path)) {
            if (preg_match("/[0-9]{4}/", $visibility)) {
                return preg_match("/0[1-7]00/", $visibility) ? Filesystem::VISIBILITY_PRIVATE : Filesystem::VISIBILITY_PUBLIC ;
            }
            return $visibility;
        }
        throw new FilesystemException("Can not get visibility of `{$path}`.");
    }

    /**
     * {@inheritDoc}
     */
    public function setVisibility(string $path, string $visibility) : Filesystem
    {
        try {
            if (!$this->driver->setVisibility($path, $visibility)) {
                throw new FilesystemException("Can not set `{$visibility}` visibility to `{$path}`.");
            }
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(string $path, string $data, string $separator = "\n") : Filesystem
    {
        return $this->exists($path) ? $this->put($path, $data.$separator.$this->get($path)) : $this->put($path, $data) ;
    }

    /**
     * {@inheritDoc}
     */
    public function append(string $path, string $data, string $separator = "\n") : Filesystem
    {
        return $this->exists($path) ? $this->put($path, $this->get($path).$separator.$data) : $this->put($path, $data) ;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string ...$paths) : Filesystem
    {
        foreach ($paths as $path) {
            try {
                $deleter = $this->isDirectory($path) ? 'deleteDir' : 'delete' ;
                if (!$this->driver->$deleter($path)) {
                    throw new FilesystemException("Can not delete `{$path}`.");
                }
            } catch (FileNotFoundException $e) {
                // Do not rethrow (File not found means already deleted)
            } catch (FlysystemFileNotFoundException $e) {
                // Do not rethrow (File not found means already deleted)
            }
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clean(string $directory = '/') : Filesystem
    {
        return $this->delete(...$this->ls($directory));
    }

    /**
     * {@inheritDoc}
     */
    public function copy(string $from, string $to, bool $replace = false) : Filesystem
    {
        try {
            if ($replace) {
                $this->delete($to);
            }
            if ($this->isDirectory($from)) {
                if ($this->exists($to)) {
                    throw new FilesystemException("Can not copy from `{$from}` to `{$to}`. `{$to}` directory already exists.");
                }
                foreach ($this->ls($from, '*', null, true) as $content) {
                    $path = $to.'/'.Strings::ltrim($content, Strings::ltrim($from, '/'));
                    if ($this->isDirectory($content)) {
                        $this->mkdir($path);
                    } else {
                        if (!$this->driver->copy($content, $path)) {
                            throw new FilesystemException("Can not copy from `{$content}` to `{$path}`.");
                        }
                    }
                }
            } else {
                if (!$this->driver->copy($from, $to)) {
                    throw new FilesystemException("Can not copy from `{$from}` to `{$to}`.");
                }
            }
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function move(string $from, string $to, bool $replace = false) : Filesystem
    {
        try {
            if ($replace) {
                $this->delete($to);
            }
            if (!$this->driver->rename($from, $to)) {
                throw new FilesystemException("Can not move from `{$from}` to `{$to}`.");
            }
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isFile(string $path) : bool
    {
        return $this->metadata($path)['type'] === 'file';
    }

    /**
     * {@inheritDoc}
     */
    public function isDirectory(string $path) : bool
    {
        return $this->metadata($path)['type'] === 'dir';
    }

    /**
     * {@inheritDoc}
     */
    public function size(string $path) : int
    {
        try {
            $size = $this->driver->getSize($path);
            if ($size === false) {
                throw new FilesystemException("Can not get size from `{$path}`.");
            }
            return $size;
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function metadata(string $path) : array
    {
        try {
            if ($meta_data = $this->driver->getMetadata($path)) {
                return $meta_data;
            }
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }

        throw new FilesystemException("Can not get meta data of `{$path}`.");
    }

    /**
     * {@inheritDoc}
     */
    public function mimeType(string $path) : ?string
    {
        try {
            MimeTypes::class;
            $mime_type = $this->driver->getMimetype($path);
            $mime_type = $mime_type === 'text/plain' ? MimeType::detectByFilename($path) : $mime_type ;
            return $mime_type === false ? null : $mime_type ;
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function lastModified(string $path) : DateTime
    {
        try {
            $time = $this->driver->getTimestamp($path);
            if ($time === false) {
                throw new FilesystemException("Can not get last modified `{$path}`.");
            }
            return new DateTime((int)$time);
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function url(string $path) : string
    {
        if ($this->getVisibility($path) !== Filesystem::VISIBILITY_PUBLIC) {
            throw new FileNotFoundException("{$path} is not public.");
        }

        $adapter = $this->adapter instanceof CachedAdapter ? $this->adapter->getAdapter() : $this->adapter ;
        if (method_exists($adapter, 'getUrl')) {
            return $adapter->getUrl($path);
        }
        if ($adapter instanceof AwsS3Adapter) {
            if (! is_null($url = $this->driver->getConfig()->get('url'))) {
                return Path::normalize($url.'/'.$adapter->getPathPrefix().$path);
            }
            return $adapter->getClient()->getObjectUrl($adapter->getBucket(), $adapter->getPathPrefix().$path);
        }
        if ($adapter instanceof AbstractFtpAdapter || $adapter instanceof Local) {
            $url = $this->driver->getConfig()->get('url');
            return Path::normalize($url ? "{$url}/{$path}" : "/{$path}") ;
        }
        throw new FilesystemException('This adapter does not support retrieving URLs.');
    }

    /**
     * {@inheritDoc}
     */
    public function readStream(string $path)
    {
        try {
            return $this->driver->readStream($path) ?: null;
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function ls(?string $directory = null, $pattern = '*', ?string $type = null, bool $recursive = false, string $matching_mode = Filesystem::MATCHING_MODE_WILDCARD) : array
    {
        try {
            return $this->filter($this->driver->listContents($directory, $recursive), $type, $pattern, $matching_mode);
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function files(?string $directory = null, $pattern = '*', bool $recursive = false, string $matching_mode = Filesystem::MATCHING_MODE_WILDCARD) : array
    {
        return $this->ls($directory, $pattern, 'file', $recursive, $matching_mode);
    }

    /**
     * {@inheritDoc}
     */
    public function directories(?string $directory = null, $pattern = '*', bool $recursive = false, string $matching_mode = Filesystem::MATCHING_MODE_WILDCARD) : array
    {
        return $this->ls($directory, $pattern, 'dir', $recursive, $matching_mode);
    }

    /**
     * Filtered given contents lists by file type and pattern (wildcard or regex)
     *
     * @param array $lists
     * @param string|null $type (default: null for not filtered)
     * @param string|string[] $pattern (default: '*' that all matching pattern for 'wildcard' matching mode)
     * @param string $matching_mode Filesystem::MATCHING_MODE_* 'wildcard' or 'regex' (default: Filesystem::MATCHING_MODE_WILDCARD)
     * @return array of matching paths
     */
    protected function filter(array $lists, ?string $type = null, $pattern = '*', string $matching_mode = Filesystem::MATCHING_MODE_WILDCARD) : array
    {
        return Stream::of($lists, true)
            ->where(function ($content) use ($type) { return $type === null ? true : $content['type'] === $type; })
            ->pluck('path')
            ->where(function ($path) use ($pattern, $matching_mode) {
                return $matching_mode === Filesystem::MATCHING_MODE_WILDCARD ? Strings::wildmatch($path, $pattern) : Strings::match($path, $pattern) ;
            })
            ->values()
            ->return()
            ;
    }

    /**
     * {@inheritDoc}
     */
    public function mkdir(string $path, array $config = []) : Filesystem
    {
        if (!$this->driver->createDir($path, $config)) {
            throw new FilesystemException("Can not create new directory to {$path}.");
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function flush() : Filesystem
    {
        if ($this->adapter instanceof CachedAdapter) {
            $this->adapter->getCache()->flush();
        }
        return $this;
    }
}
