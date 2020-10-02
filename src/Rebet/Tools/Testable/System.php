<?php
namespace Rebet\Tools\Testable;

use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Tinker;
use Rebet\Tools\Utility\Arrays;

/**
 * System Class
 *
 * It is a class aimed to make it possible to hook on unit tests about functions that operate only with SAPI such as header.
 * and functions that operate only with SAPI such as header.
 * Note that language structures such as exit and die are not handled in this class because it is desirable to exclude them in terms of testability.
 *
 * @method static void  header(string $header, bool $replace = true, int $http_response_code = null)
 * @method static array headers_list()
 * @method static array dns_get_record(string $hostname, int $type = DNS_ANY, ?array &$authns = null, ?array &$addtl = null, bool $raw = false)
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class System
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'emulators' => [
                'header' => [
                    'emulator' => function (string $header, bool $replace = true, int $http_response_code = null) {
                        $emulated_header = &System::memory('emulated_header');
                        $http_status     = System::datasets('header', 'http_status');
                        if (\preg_match('/^HTTP\//', $header)) {
                            $emulated_header['http'] = [$header];
                        } elseif ($http_response_code !== null && isset($http_status[$http_response_code])) {
                            $emulated_header['http'] = ["HTTP/1.1 {$http_response_code} ".$http_status[$http_response_code]];
                        } elseif (!isset($emulated_header['http'])) {
                            $emulated_header['http'] = ['HTTP/1.1 200 OK'];
                        }

                        if (\strpos($header, ':') !== false) {
                            $parts = \explode(':', $header, 2);
                            $key   = \strtolower($parts[0]);
                            if (!isset($emulated_header[$key])) {
                                $emulated_header[$key] = [];
                            }
                            if ($replace) {
                                $emulated_header[$key] = [$header];
                            } else {
                                $emulated_header[$key][] = $header;
                            }
                        }
                    },
                    'datasets' => [
                        'http_status' => [
                            100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing', 103 => 'Early Hints',
                            200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-Status', 208 => 'Already Reported', 226 => 'IM Used',
                            300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect',
                            400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Payload Too Large', 414 => 'URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Range Not Satisfiable', 417 => 'Expectation Failed', 418 => "I'm a teapot", 421 => 'Misdirected Request', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 426 => 'Upgrade Required', 451 => 'Unavailable For Legal Reasons',
                            500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage', 508 => 'Loop Detected', 509 => 'Bandwidth Limit Exceeded', 510 => 'Not Extended', 511 => 'Network Authentication Required',
                        ]
                    ],
                ],
                'headers_list' => [
                    'emulator' => function () {
                        return Arrays::flatten(\array_values(System::memory('emulated_header')));
                    },
                ],
                'dns_get_record' => [
                    'emulator' => function (string $hostname, int $type = DNS_ANY, ?array &$authns = null, ?array &$addtl = null, bool $raw = false) : array {
                        $emulated_dns = System::datasets('dns_get_record', 'emulated_dns');
                        if (isset($emulated_dns[$hostname])) {
                            $c = Tinker::with($emulated_dns[$hostname], true);
                            return array_values($c->where(function ($v) use ($type) {
                                $vt = Reflector::get($v, 'type');
                                switch (true) {
                                    case DNS_ANY === $type: return true;
                                    case DNS_ALL === $type: return true;
                                    case DNS_A & $type && $vt === 'A': return true;
                                    case DNS_CNAME & $type && $vt === 'CNAME': return true;
                                    case DNS_HINFO & $type && $vt === 'HINFO': return true;
                                    // case DNS_CAA & $type && $vt === 'CAA': return true; // PHP Warning:  Use of undefined constant DNS_CAA - assumed 'DNS_CAA' (this will throw an Error in a future version of PHP)
                                    case DNS_MX & $type && $vt === 'MX': return true;
                                    case DNS_NS & $type && $vt === 'NS': return true;
                                    case DNS_PTR & $type && $vt === 'PTR': return true;
                                    case DNS_SOA & $type && $vt === 'SOA': return true;
                                    case DNS_TXT & $type && $vt === 'TXT': return true;
                                    case DNS_AAAA & $type && $vt === 'AAAA': return true;
                                    case DNS_SRV & $type && $vt === 'SRV': return true;
                                    case DNS_NAPTR & $type && $vt === 'NAPTR': return true;
                                    case DNS_A6 & $type && $vt === 'A6': return true;
                                    case DNS_NAPTR & $type && $vt === 'NAPTR': return true;
                                }
                                return false;
                            })->return());
                        }
                        return [];
                    },
                    'datasets' => [
                        'emulated_dns' => [
                            'sample.local' => [
                                ["host" => "sample.local", "class" => "IN", "ttl" => 60  , "type" => "A", "ip" => "127.0.0.1"],
                                ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 1, "target" => "mx.sample.local"],
                                ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 5, "target" => "alt1.mx.sample.local"],
                                ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 5, "target" => "alt2.mx.sample.local"],
                                ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "TXT", "txt" => "v=spf1 mx ~all", "entries" => ["v=spf1 mx ~all"]],
                                ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "SOA", "mname" => "ns1.p01.dynect.net", "rname" => "hostmaster.sample.local", "serial" => 1234567890, "refresh" => 3600, "retry" => 600, "expire" => 604800, "minimum-ttl" => 60],
                                ["host" => "sample.local", "class" => "IN", "ttl" => 836 , "type" => "NS", "target" => "ns1.p01.dynect.net"],
                                ["host" => "sample.local", "class" => "IN", "ttl" => 836 , "type" => "NS", "target" => "ns2.p01.dynect.net"],
                            ],
                        ]
                    ],
                ],
            ],
        ];
    }

    /**
     * Testing mode or not.
     *
     * @var bool
     */
    protected static $is_testing = false;

    /**
     * Memory data needed for php function emurating.
     *
     * @var array of ['name' => [ *Registered data you want* ]]
     */
    protected static $memory = [];

    /**
     * Get memory data.
     *
     * @param string $name
     * @return array
     */
    public static function &memory(string $name) : array
    {
        if (!isset(static::$memory[$name])) {
            static::$memory[$name] = [];
        }
        return static::$memory[$name];
    }

    /**
     * Clear memory data needed for php function emurating.
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$memory = [];
    }

    /**
     * Get / Set dataset of given name.
     *
     * @param string $function_name
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public static function datasets(string $function_name, string $name, $value = null)
    {
        if ($value === null) {
            return static::config("emulators.{$function_name}.datasets.{$name}", false);
        }
        static::setConfig(['emulators' => [$function_name => ['datasets' => [$name => $value]]]]);
        return $value;
    }

    /**
     * Register php function emulator.
     *
     * @param string $function_name
     * @param \Closure $emurator
     * @param array $datasets
     * @return void
     */
    public static function emulator(string $function_name, \Closure $emurator, array $datasets = [])
    {
        static::setConfig([
            'emulators' => [
                $function_name => [
                    'datasets' => $datasets,
                    'emulator' => $emurator,
                ]
            ]
        ]);
    }

    /**
     * Get/Set testing mode.
     *
     * @param bool|null $is_testing
     * @return bool
     */
    public static function testing(?bool $is_testing = null) : bool
    {
        return $is_testing === null ? static::$is_testing : static::$is_testing = $is_testing ;
    }

    /**
     * It checks given php function name can be emulatable or not.
     *
     * @param [type] $function_name
     * @return bool
     */
    public static function emulatable($function_name) : bool
    {
        return static::config("emulators.{$function_name}.emulator", false) !== null;
    }

    /**
     * Delegate php function directly, but call emulator if it's testing.
     *
     * @param string $name
     * @param array $args
     * @return void
     */
    public static function __callStatic($name, array $args)
    {
        if (static::testing() && static::emulatable($name)) {
            $emulator = static::config("emulators.{$name}.emulator");
            return $emulator(...$args);
        }
        return call_user_func('\\'.$name, ...$args);
    }

    /**
     * No instantiation
     */
    private function __construct()
    {
    }
}
