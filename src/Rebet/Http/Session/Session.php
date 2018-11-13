<?php
namespace Rebet\Http\Session;

use Rebet\Config\Configurable;
use Rebet\Http\Session\Storage\Bag\AttributeBag;
use Rebet\Http\Session\Storage\Bag\FlashBag;
use Rebet\Http\Session\Storage\SessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

/**
 * Session Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Session
{
    use Configurable;

    public static function defaultConfig() : array
    {
        return [
            'storage' => SessionStorage::class,
        ];
    }

    /**
     * Current Session instance.
     *
     * @var Session|null
     */
    private static $current = null;

    /**
     * Session storage for this Session.
     *
     * @var SessionStorageInterface
     */
    protected $storage;

    /**
     * Create the session.
     *
     * @param SessionStorageInterface $storage (default: depend on configure)
     */
    public function __construct(SessionStorageInterface $storage = null)
    {
        $this->storage = $storage ?? static::configInstantiate('storage');
        $this->storage->registerBag(new AttributeBag('attributes'));
        $this->storage->registerBag(new FlashBag('flashes'));
        $this->storage->registerBag(new FlashBag('fallbacks'));
        static::$current = $this;
    }

    /**
     * Get the current (latest instantiate) session instance.
     */
    public static function current() : ?self
    {
        return static::$current;
    }

    /**
     * Get the attributes session bag.
     *
     * @return AttributeBag
     */
    public function attribute() : AttributeBag
    {
        return $this->storage->getBag('attributes');
    }

    /**
     * Get the flashes session bag.
     *
     * @return FlashBag
     */
    public function flash() : FlashBag
    {
        return $this->storage->getBag('flashes');
    }

    /**
     * Get the fallbacks session bag.
     *
     * @return FlashBag
     */
    public function fallback() : FlashBag
    {
        return $this->storage->getBag('fallbacks');
    }

    /**
     * Get the meta data bag.
     *
     * @return MetadataBag
     */
    public function meta() : MetadataBag
    {
        return $this->storage->getMetadataBag();
    }

    /**
     * Start the session
     *
     * @return boolean
     */
    public function start() : bool
    {
        return $this->storage->start();
    }

    /**
     * It checks the session is started.
     *
     * @return boolean
     */
    public function isStarted() : bool
    {
        return $this->storage->isStarted();
    }

    /**
     * Invalidates the current session.
     *
     * Clears all session attributes and flashes and regenerates the
     * session and deletes the old session from persistence.
     *
     * @param int $lifetime Sets the cookie lifetime for the session cookie. A null value
     *                      will leave the system settings unchanged, 0 sets the cookie
     *                      to expire with browser session. Time is in seconds, and is
     *                      not a Unix timestamp.
     *
     * @return bool True if session invalidated, false if error
     */
    public function invalidate($lifetime = null)
    {
        $this->storage->clear();
        return $this->migrate(true, $lifetime);
    }

    /**
     * Migrates the current session to a new session id while maintaining all
     * session attributes.
     *
     * @param bool $destroy  Whether to delete the old session or leave it to garbage collection
     * @param int  $lifetime Sets the cookie lifetime for the session cookie. A null value
     *                       will leave the system settings unchanged, 0 sets the cookie
     *                       to expire with browser session. Time is in seconds, and is
     *                       not a Unix timestamp.
     *
     * @return bool True if session migrated, false if error
     */
    public function migrate($destroy = false, $lifetime = null)
    {
        return $this->storage->regenerate($destroy, $lifetime);
    }

    /**
     * Force the session to be saved and closed.
     *
     * This method is generally not required for real sessions as
     * the session will be automatically saved at the end of
     * code execution.
     */
    public function save() : void
    {
        $this->storage->save();
    }

    /**
     * Get the session ID.
     *
     * @return string
     */
    public function getId() : string
    {
        return $this->storage->getId();
    }

    /**
     * Set the session ID.
     *
     * @param string $id
     * @return void
     */
    public function setId(string $id) : void
    {
        if ($this->storage->getId() !== $id) {
            $this->storage->setId($id);
        }
    }
}
