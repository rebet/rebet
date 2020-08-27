<?php
namespace Rebet\Cache\Adapter;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Rebet\Cache\Exception\UnsupportedTaggingException;

/**
 * Adapter Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Adapter extends CacheItemPoolInterface
{
    /**
     * It checks the adapter supported to tag or not.
     *
     * @return bool
     */
    public function taggable() : bool;

    /**
     * Persists a cache item immediately with tags.
     * If the given tag is empty then this method just call save() as it is.
     *
     * @param CacheItemInterface $item The cache item to save.
     * @param array $tags The array of tags to save. (default: [])
     * @return bool True if the item was successfully persisted. False if there was an error.
     * @throws UnsupportedTaggingException when tags given but the adapter does not support tag
     */
    public function saveWithTags(CacheItemInterface $item, array $tags = []);

    /**
     * Sets a cache item to be persisted later with tag.
     * If the given tag is empty then this method just call saveDeferred() as it is.
     *
     * @param CacheItemInterface $item The cache item to save.
     * @param array $tags The array of tags to save. (default: [])
     * @return bool False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     * @throws UnsupportedTaggingException when tags given but the adapter does not support tag
     */
    public function saveDeferredWithTags(CacheItemInterface $item, array $tags = []);

    /**
     * Removes all the cached entries associated with the given tag names.
     *
     * @param string[] $tags The array of tags to remove.
     * @return bool
     * @throws UnsupportedTaggingException when the adapter does not support tag
     */
    public function clearByTags(array $tags) : bool ;

    /**
     * Execute pruning (deletion) of all expired cache items.
     * If the adapter not supported pruning then just do nothing and return false.
     *
     * @return bool
     */
    public function prune() : bool;
}
