<?php
declare(strict_types=1);

namespace Rebet\Filesystem;

use League\Flysystem\Filesystem as FlysystemFilesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use Psr\Http\Message\StreamInterface;
use Rebet\Filesystem\Exception\FileNotFoundException;
use Rebet\Filesystem\Exception\FilesystemException;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Tinker\Tinker;
use Rebet\Tools\Utility\Path;
use Rebet\Tools\Utility\Strings;
use Rebet\Tools\Utility\Utils;

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

    /**
     * {@inheritDoc}
     * @see https://github.com/rebet/rebet/blob/master/src/Rebet/Application/Console/Command/skeltons/configs/filesystem.letterpress.php
     */
    public static function defaultConfig()
    {
        return [
            'driver' => FlysystemFilesystem::class,
        ];
    }

    /**
     * @var FilesystemOperator
     */
    protected $driver;

    /**
     * @var FilesystemAdapter
     */
    protected $adapter;

    /**
     * @var array<string, mixed>
     */
    protected $config;

    /**
     * {@inheritDoc}
     */
    public function __construct(FilesystemAdapter $adapter, $config = null)
    {
        $driver        = static::config('driver');
        $config        = (array) ($config ?? []);
        $this->driver  = new $driver($adapter, $config);
        $this->adapter = $adapter;
        $this->config  = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function driver() : FilesystemOperator
    {
        return $this->driver;
    }

    /**
     * {@inheritDoc}
     */
    public function adapter() : FilesystemAdapter
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
    public function isFile(string $path) : bool
    {
        return $this->driver->fileExists($path);
    }

    /**
     * {@inheritDoc}
     */
    public function isDirectory(string $path) : bool
    {
        return $this->driver->directoryExists($path);
    }

    /**
     * {@inheritDoc}
     */
    public function path(string $path = '/') : string
    {
        $prefixer = Reflector::get($this->adapter, 'prefixer', null, true);
        if ($prefixer === null) {
            throw new FilesystemException('This adapter does not support path resolution.');
        }
        return Path::normalize($prefixer->prefixPath($path));
    }

    /**
     * Convert Flysystem exception to Rebet exception.
     *
     * @param \Exception $e
     * @return FilesystemException
     */
    protected function convertException(\Exception $e) : FilesystemException
    {
        return $e instanceof FilesystemException ? $e : new FilesystemException($e->getMessage(), $e) ;
    }

    /**
     * Assert the given path (file or directory) exists, otherwise throw FileNotFoundException.
     *
     * @param string $path
     * @return void
     * @throws FileNotFoundException
     */
    protected function assertExists(string $path) : void
    {
        if (!$this->exists($path)) {
            throw new FileNotFoundException("File not found at path: {$path}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $path) : string
    {
        if (!$this->driver->fileExists($path)) {
            throw new FileNotFoundException("File not found at path: {$path}");
        }
        try {
            return $this->driver->read($path);
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $path, $contents, $options = []) : string
    {
        $options = is_string($options) ? ['visibility' => $options] : (array) $options ;
        $stream  = null;
        $ext     = null;
        switch (true) {
            case $contents instanceof \SplFileInfo:
                $stream = fopen($contents->getRealPath(), 'r');
                $ext    = strtolower($contents->getExtension()) ?: null;
                break;
            case $contents instanceof StreamInterface:
                $stream = $contents->detach();
                break;
            default:
                $stream = $contents;
        }
        $ext  = $options['.ext'] ?? $ext ?? null ;
        $path = str_replace('{.ext}', Utils::isBlank($ext) ? '' : ".{$ext}", $path);
        try {
            if (is_resource($stream)) {
                $this->driver->writeStream($path, $stream, $options);
            } else {
                $this->driver->write($path, $stream, $options);
            }
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
        return $path;
    }

    /**
     * {@inheritDoc}
     */
    public function putFile(string $path, $file, $options = []) : string
    {
        return $this->put($path, is_string($file) ? new \SplFileInfo($file) : $file, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function getVisibility(string $path) : string
    {
        try {
            return $this->driver->visibility($path);
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setVisibility(string $path, string $visibility) : Filesystem
    {
        $this->assertExists($path);
        try {
            $this->driver->setVisibility($path, $visibility);
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
        $this->exists($path) ? $this->put($path, $data.$separator.$this->get($path)) : $this->put($path, $data) ;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function append(string $path, string $data, string $separator = "\n") : Filesystem
    {
        $this->exists($path) ? $this->put($path, $this->get($path).$separator.$data) : $this->put($path, $data) ;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string ...$paths) : Filesystem
    {
        foreach ($paths as $path) {
            try {
                if ($this->isDirectory($path)) {
                    $this->driver->deleteDirectory($path);
                } else {
                    $this->driver->delete($path);
                }
            } catch (\Exception $e) {
                throw $this->convertException($e);
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
        $this->assertExists($from);
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
                        $this->driver->copy($content, $path);
                    }
                }
            } else {
                if (!$replace && $this->exists($to)) {
                    throw new FilesystemException("File already exists at path: {$to}");
                }
                $this->driver->copy($from, $to);
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
        $this->assertExists($from);
        try {
            if ($replace) {
                $this->delete($to);
            } elseif ($this->exists($to)) {
                throw new FilesystemException("File already exists at path: {$to}");
            }
            $this->driver->move($from, $to);
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function size(string $path) : int
    {
        $this->assertExists($path);
        try {
            return $this->driver->fileSize($path);
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function metadata(string $path) : array
    {
        $this->assertExists($path);
        try {
            $is_dir = $this->isDirectory($path);
            return [
                'type'      => $is_dir ? 'dir' : 'file',
                'path'      => $path,
                'size'      => $is_dir ? null : $this->driver->fileSize($path),
                'timestamp' => $this->driver->lastModified($path),
            ];
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function mimeType(string $path) : string|null
    {
        $this->assertExists($path);
        try {
            if ($this->isDirectory($path)) {
                return 'directory';
            }
            return $this->driver->mimeType($path);
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function lastModified(string $path) : DateTime
    {
        $this->assertExists($path);
        try {
            return new DateTime($this->driver->lastModified($path));
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

        $adapter = $this->adapter;
        if (method_exists($adapter, 'getUrl')) {
            return $adapter->getUrl($path);
        }
        if ($adapter instanceof PublicUrlGenerator) {
            $url = $this->config['url'] ?? null;
            return $url ? Path::normalize($url.'/'.$path) : $this->driver->publicUrl($path);
        }
        if ($adapter instanceof LocalFilesystemAdapter) {
            $url = $this->config['url'] ?? null;
            return Path::normalize($url ? "{$url}/{$path}" : "/{$path}");
        }
        throw new FilesystemException('This adapter does not support retrieving URLs.');
    }

    /**
     * {@inheritDoc}
     */
    public function readStream(string $path)
    {
        $this->assertExists($path);
        try {
            return $this->driver->readStream($path);
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function ls(string|null $directory = null, $pattern = '*', string|null $type = null, bool $recursive = false, string $matching_mode = Filesystem::MATCHING_MODE_WILDCARD) : array
    {
        try {
            $contents = [];
            foreach ($this->driver->listContents($directory ?? '/', $recursive) as $item) {
                $contents[] = ['type' => $item->type(), 'path' => $item->path()];
            }
            usort($contents, function ($a, $b) { return $a['path'] <=> $b['path']; });
            return $this->filter($contents, $type, $pattern, $matching_mode);
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function files(string|null $directory = null, $pattern = '*', bool $recursive = false, string $matching_mode = Filesystem::MATCHING_MODE_WILDCARD) : array
    {
        return $this->ls($directory, $pattern, 'file', $recursive, $matching_mode);
    }

    /**
     * {@inheritDoc}
     */
    public function directories(string|null $directory = null, $pattern = '*', bool $recursive = false, string $matching_mode = Filesystem::MATCHING_MODE_WILDCARD) : array
    {
        return $this->ls($directory, $pattern, 'dir', $recursive, $matching_mode);
    }

    /**
     * Filtered given contents lists by file type and pattern (wildcard or regex)
     *
     * @param array<int, array{type: string, path: string}> $lists
     * @param string|null $type (default: null for not filtered)
     * @param string|string[] $pattern (default: '*' that all matching pattern for 'wildcard' matching mode)
     * @param string $matching_mode Filesystem::MATCHING_MODE_* 'wildcard' or 'regex' (default: Filesystem::MATCHING_MODE_WILDCARD)
     * @return array<int, string> of matching paths
     */
    protected function filter(array $lists, string|null $type = null, $pattern = '*', string $matching_mode = Filesystem::MATCHING_MODE_WILDCARD) : array
    {
        return Tinker::with($lists, true)
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
        try {
            $this->driver->createDirectory($path, $config);
        } catch (\Exception $e) {
            throw $this->convertException($e);
        }
        return $this;
    }
}
