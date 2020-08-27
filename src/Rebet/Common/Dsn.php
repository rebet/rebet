<?php
namespace Rebet\Common;

use Rebet\Common\Exception\LogicException;

/**
 * DSN Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Dsn
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Parse given DSN string.
     * This parser provides JUST simple dsn parsing.
     *
     * This method can be parsed '[<scheme>://][[<user>:]<pass>@]<host>[:<port>][/<name>][?key=value[&...]]' like below.
     *  - localhost
     *  - pass@localhost
     *  - user:pass@localhost
     *  - localhost:11211
     *  - localhost?key=value
     *  - localhost:11211?key=value
     *  - scheme://localhost
     *  - redis://pass@localhost:6379/12?timeout=5
     *  - memcache://user:pass@localhost?weight=0
     * to
     *  [
     *     'scheme' => 'scheme',
     *     'user'   => 'user',
     *     'pass'   => 'pass',
     *     'host'   => 'host',
     *     'port'   => 'port',
     *     'name'   => 'name', (not include the leading "/")
     *     'query'  => ['key' => 'value', ...],
     *  ]
     *
     * @param string|null $dsn
     * @return array
     */
    public static function parse(?string $dsn) : array
    {
        if (empty($dsn)) {
            return [];
        }

        $matcher = [];
        preg_match('/^(?:(?<scheme>[^:]*):\/\/)?(?:(?:(?<user>[^:]*):)?(?<pass>[^@]*)@)?(?<host>[^:\/?]*)(:(?<port>[0-9]*))?(\/(?<name>[^?]*))?(\?(?<query>.*))?/u', $dsn, $matcher);
        $parsed = [];
        foreach (['scheme', 'user', 'pass', 'host', 'port', 'name', 'query'] as $key) {
            if (empty($matcher[$key])) {
                if ($key === 'host') {
                    throw new LogicException("DSN parse failed, the DSN must have host part but given DSN '{$dsn}' not include it.");
                }
                continue;
            }
            if ($key === 'query') {
                $query = [];
                parse_str($matcher[$key], $query);
                $parsed[$key] = $query;
            } else {
                $parsed[$key] = $matcher[$key];
            }
        }
        return $parsed;
    }
}
