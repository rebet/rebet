<?php
namespace Rebet\Event;

use Rebet\Common\Exception\LogicException;
use Rebet\Common\Reflector;
use Rebet\Config\Config;
use Rebet\Config\Configurable;

/**
 * Event Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Event
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'listeners' => [],
        ];
    }

    /**
     * The compiled listeners.
     *
     * @var array [event => [listener, ...]]
     */
    protected static $listeners = null;

    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Clear the compiled listeners.
     *
     * @return void
     */
    public static function clear() : void
    {
        Config::clear(static::class);
        static::$listeners = null;
    }

    /**
     * Add event listener.
     * An event listener must have handle(EventClass $event) method or function(EventClass $event) with type hinting of event class.
     *
     * @param mixed $listeners
     * @return void
     */
    public static function listen($listeners) : void
    {
        $listeners = is_array($listeners) ? $listeners : func_get_args() ;
        static::setConfig(['listeners>' => $listeners]);
        if (static::$listeners !== null) {
            foreach ($listeners as $listener) {
                [$event, $listener]          = static::resolve($listener);
                static::$listeners[$event][] = $listener;
            }
        }
    }

    /**
     * Dispatch the event to listeners.
     *
     * @param mixed $event
     * @return void
     */
    public static function dispatch($event) : void
    {
        static::compile();
        foreach (static::$listeners as $listen => $listeners) {
            if (!Reflector::typeOf($event, $listen)) {
                continue;
            }
            foreach ($listeners as $listener) {
                if ($listener instanceof \Closure) {
                    $listener($event);
                    continue;
                }
                $listener->handle($event);
            }
        }
    }

    /**
     * Compile the event listeners.
     *
     * @return void
     */
    protected static function compile() : void
    {
        if (static::$listeners !== null) {
            return;
        }

        static::$listeners = [];
        foreach (static::config('listeners', false, []) as $listener) {
            [$event, $listener]          = static::resolve($listener);
            static::$listeners[$event][] = $listener;
        }
    }

    /**
     * Resolve what event should be listened the given listener.
     *
     * @param mixed $listener
     * @return array [event, listener]
     */
    protected static function resolve($listener) : array
    {
        if (is_callable($listener)) {
            return [Reflector::getTypeHintOf($listener, 0), $listener];
        }
        $listener = Reflector::instantiate($listener);
        if (!method_exists($listener, 'handle')) {
            throw LogicException::by("Event listener ".get_class($listener)." must have 'handle' method or callable.");
        }
        $method = new \ReflectionMethod($listener, 'handle');
        return [Reflector::getTypeHint($method->getParameters()[0]), $listener];
    }
}
