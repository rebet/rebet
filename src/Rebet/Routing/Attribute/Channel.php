<?php
declare(strict_types=1);

namespace Rebet\Routing\Attribute;

/**
 * Channel Attribute
 *
 * USAGE:
 *  - #[Channel("web")]
 *  - #[Channel("web", "api")]
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class Channel
{
    /**
     * @var array<string> of allow channels
     */
    public array $allows = [];

    /**
     * Create Channel attribute.
     *
     * @param string ...$allows of allow channels
     */
    public function __construct(string ...$allows)
    {
        $this->allows = $allows;
    }

    /**
     * Check acceptable the given channel.
     * NOTE: If no allow channel is configured, any channel will be allowed.
     *
     * @param string $channel
     * @return boolean
     */
    public function allow(string $channel) : bool
    {
        return empty($this->allows) ? true : in_array($channel, $this->allows) ;
    }

    /**
     * Check acceptable the given channel.
     * NOTE: If no allow channel is configured, any channel will be allowed.
     *
     * @param string $channel
     * @return boolean
     */
    public function reject(string $channel) : bool
    {
        return !$this->allow($channel);
    }
}
