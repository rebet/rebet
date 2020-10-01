<?php
namespace Rebet\Http\Session;

use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Securities;
use Rebet\Tools\Strings;
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
     * Note: without scope then token will be reusable, with scope then token will be one time token.
     *
     * @param mixed ...$scopes
     * @return string|null
     */
    public function token(...$scopes) : ?string
    {
        $key = static::createTokenKey(...$scopes);
        return empty($scopes) ? $this->attribute()->get($key) : $this->flash()->peek($key);
    }

    /**
     * Generate the CSRF token value and set it to session.
     * Note: without scope then token will be reusable, with scope then token will be one time token.
     *
     * @param mixed ...$scopes
     * @return string of generated token
     */
    public function generateToken(...$scopes) : string
    {
        $token = Securities::randomCode(40);
        $key   = static::createTokenKey(...$scopes);
        empty($scopes) ? $this->attribute()->set($key, $token) : $this->flash()->set($key, $token) ;
        return $token;
    }

    /**
     * Generate the reusable (not one time) CSRF token value when the token still not set.
     * If the token already set, then just return it.
     *
     * @return string
     */
    public function initReusableToken() : string
    {
        $token = $this->token();
        if (empty($token)) {
            $token = $this->generateToken();
        }
        return $token;
    }

    /**
     * Verify the CSRF token value.
     * Note: without scope then token will be reusable, with scope then token will be one time token.
     *
     * @param string|null $token
     * @param mixed ...$scopes
     * @return bool
     */
    public function verifyToken(?string $token, ...$scopes) : bool
    {
        $key    = static::createTokenKey(...$scopes);
        $expect = empty($scopes) ? $this->attribute()->get($key) : $this->flash()->get($key);
        return $token === null || $expect == null ? false : hash_equals($token, $expect);
    }

    /**
     * Create token key from given scope.
     *
     * @param mixed ...$scopes
     * @return string
     * @throws LogicException when token scope contains ':'.
     */
    public static function createTokenKey(...$scopes) : string
    {
        if (empty($scopes)) {
            return '_token';
        }
        foreach ($scopes as $scope) {
            if (Strings::contains("{$scope}", ':')) {
                throw new LogicException("Invalid token scope name '{$scope}' found. Token scope can not contains ':'.");
            }
        }
        return '_token:'.implode(':', $scopes);
    }

    /**
     * Analyze token scope from given key.
     *
     * @param string $key
     * @return array
     * @throws LogicException when invalid token key was given.
     */
    public static function analyzeTokenScope(string $key) : array
    {
        if (!Strings::startsWith($key, '_token')) {
            throw new LogicException("Invalid token key '{$key}' was given. Token key must be starts with '_token'.");
        }
        $scopes = Strings::trim(Strings::ltrim($key, '_token', 1), ':');
        return empty($scopes) ? [] : explode(':', $scopes) ;
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
