<?php
namespace Rebet\Routing\Annotation;

/**
 * Channel Annotation
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
     * @var array
     */
    public $allows;

    /**
     * @var array
     */
    public $rejects;

    /**
     * Constructor.
     *
     * @param array $values value or [allows, rejects]
     */
    public function __construct(array $values)
    {
        $this->allows  = (array)($values['allows'] ?? $values['value']) ;
        $this->rejects = empty($this->allows) ? (array)($values['rejects']) : [] ;
    }

    /**
     * Check acceptable the given channel.
     *
     * @param string $channel
     * @return boolean
     */
    public function allow(string $channel) : bool
    {
        return empty($this->rejects) ? in_array($channel, $this->allows) : !in_array($channel, $this->rejects) ;
    }

    /**
     * Check acceptable the given channel.
     *
     * @param string $channel
     * @return boolean
     */
    public function reject(string $channel) : bool
    {
        return !$this->allow($channel);
    }
}
