<?php
namespace Rebet\Routing\Annotation;

/**
 * Channel Annotation
 *
 * USAGE:
 *  - @Channel("web")           ... same as @Channel(allow="web")
 *  - @Channel({"web", "api"})  ... same as @Channel(allow={"web","api"})
 *  - @Channel(rejects="web")
 *  - @Channel(rejects={"web". "api"})
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
final class Channel
{
    /**
     * @var array of allow channels
     */
    public $allows = [];

    /**
     * @var array of reject channels
     */
    public $rejects = [];

    /**
     * Check acceptable the given channel.
     * NOTE: If an allow list is configured, the reject list will be ignored.
     *
     * @param string $channel
     * @return boolean
     */
    public function allow(string $channel) : bool
    {
        return !empty($this->allows) ? in_array($channel, $this->allows) : !in_array($channel, $this->rejects) ;
    }

    /**
     * Check acceptable the given channel.
     * NOTE: If an allow list is configured, the reject list will be ignored.
     *
     * @param string $channel
     * @return boolean
     */
    public function reject(string $channel) : bool
    {
        return !$this->allow($channel);
    }
}
