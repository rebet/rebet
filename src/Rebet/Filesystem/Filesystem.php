<?php
namespace Rebet\Filesystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\FileNotFoundException;

/**
 * Filesystem Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Filesystem
{
    /**
     * Create Filesystem using given adapter and config.
     *
     * @param AdapterInterface $adapter
     * @param array|Config|null $config (default: null)
     */
    public function __construct(AdapterInterface $adapter, $config = null);

    /**
     * Get the URL for the file at the given path.
     *
     * @param string $path
     * @return string|null
     * @throws FileNotFoundException when file not found or the file is not public.
     */
    public function url(string $path) : ?string;
}
