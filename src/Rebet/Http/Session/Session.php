<?php
namespace Rebet\Http\Session;

use Rebet\Common\Securities;
use Rebet\Common\Strings;
use Rebet\Config\Configurable;
use Rebet\Http\Session\Storage\Bag\AttributeBag;
use Rebet\Http\Session\Storage\Bag\FlashBag;
use Rebet\Http\Session\Storage\Bag\MetadataBag;
use Rebet\Http\Session\Storage\SessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

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

    public static function defaultConfig()
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
        static::$current = $this;
    }

    /**
     * Clear the session.
     */
    public static function clear() : void
    {
        if (static::$current) {
            static::$current->storage->clear();
            static::$current = null;
        }
    }

    /**
     * Get the current (latest instantiate) session instance.
     */
    public static function current() : ?self
    {
        return static::$current;
    }

    /**
     * It checks exists the given name property in attribute session bag.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name) : bool
    {
        return $this->attribute()->has($name);
    }

    /**
     * Get the value from attribute session bag.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        return $this->attribute()->get($name, $default);
    }

    /**
     * Set the value to attribute session bag.
     *
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function set(string $name, $value) : self
    {
        $this->attribute()->set($name, $value);
        return $this;
    }

    /**
     * Remove the value from attribute session bag.
     *
     * @param string $name
     * @return mixed
     */
    public function remove(string $name)
    {
        return $this->attribute()->remove($name);
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
     *
     * @return self
     */
    public function save() : self
    {
        $this->storage->save();
        return $this;
    }

    /**
     * Get and Set the session ID.
     *
     * @param string|null $id (default: null)
     * @return string|self
     */
    public function id(?string $id = null)
    {
        if ($id === null) {
            return $this->storage->getId();
        }
        if ($this->storage->getId() !== $id) {
            $this->storage->setId($id);
        }
        return $this;
    }

    /**
     * Peek the CSRF token value.
     * Note: without scope then token will be fixed, with scope then token will be one time token.
     *
     * @param mixed ...$scope
     * @return string|null
     */
    public function token(...$scope) : ?string
    {
        return empty($scope) ? $this->attribute()->get('_token') : $this->flash()->peek('_token:'.implode(':', $scope));
    }

    /**
     * Generate the CSRF token value and set it to session.
     * Note: without scope then token will be fixed, with scope then token will be one time token.
     *
     * @param mixed ...$scope
     * @return string of generated token
     */
    public function generateToken(...$scope) : string
    {
        $token = Securities::randomCode(40);
        empty($scope) ? $this->attribute()->set('_token', $token) : $this->flash()->set('_token:'.implode(':', $scope), $token) ;
        return $token;
    }

    /**
     * Verify the CSRF token value.
     * Note: without scope then token will be fixed, with scope then token will be one time token.
     *
     * @param string|null $token
     * @param mixed ...$scope
     * @return bool
     */
    public function verifyToken(?string $token, ...$scope) : bool
    {
        $expect = empty($scope) ? $this->attribute()->get('_token') : $this->flash()->get('_token:'.implode(':', $scope));
        return $token === null || $expect == null ? false : hash_equals($token, $expect);
    }

    /**
     * Save inherit-data for next request.
     *
     * @param string $name
     * @param mixed $data
     * @param string|array $wildcard of request path without route prefix (default: '*')
     * @return self
     */
    public function saveInheritData(string $name, $data, $wildcard = '*') : self
    {
        $flash = $this->flash();
        $flash->set("_inherit_{$name}", array_merge(
            $flash->peek("_inherit_{$name}", []),
            [[(array)$wildcard, $data]]
        ));
        return $this;
    }

    /**
     * Load inherit-data of given request path if exists.
     * Note: This method remove all of inherit-data of given name.
     *
     * @param string $name
     * @param string $request_path without route prefix
     * @param mixed $default (default: [])
     * @return array
     */
    public function loadInheritData(string $name, string $request_path, $default = []) : array
    {
        $flash   = $this->flash();
        $inherit = [];
        foreach ($flash->get("_inherit_{$name}", []) as $key => [$wildcard, $data]) {
            if (Strings::wildmatch($request_path, $wildcard)) {
                $inherit = array_merge($inherit, $data);
            }
        }
        return empty($inherit) ? $default : $inherit ;
    }
}
