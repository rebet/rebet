<?php
namespace Rebet\Http\Session\Storage;

use Rebet\Http\Session\Storage\Bag\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Array Session Storage Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ArraySessionStorage extends MockArraySessionStorage
{
    /**
     * Create array session storage for unit test
     *
     * @param string $name
     * @param MetadataBag|null $metadata_bag (default: null)
     */
    public function __construct(string $name = 'MOCKSESSID', ?MetadataBag $metadata_bag = null)
    {
        $this->name = $name;
        $this->setMetadataBag($metadata_bag ?? new MetadataBag('_rebet_meta'));
    }

    /**
     * {@inheritdoc}
     */
    public function regenerate($destroy = false, $lifetime = null)
    {
        if (!$this->started) {
            $this->start();
        }

        if ($destroy) {
            $this->clear();
        }

        return parent::regenerate($destroy, $lifetime);
    }
}
