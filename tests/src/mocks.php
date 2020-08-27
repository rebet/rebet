<?php

/**
 * Mock class for unit testing
 */
namespace Rebet\Common {
    use Rebet\Stream\Stream;
    use Rebet\Tests\DieException;
    use Rebet\Tests\ExitException;

    class System
    {
        private const HTTP_STATUS = [
            100 => '100 Continue',
            101 => '101 Switching Protocols',
            102 => '102 Processing',
            103 => '103 Early Hints',
            200 => '200 OK',
            201 => '201 Created',
            202 => '202 Accepted',
            203 => '203 Non-Authoritative Information',
            204 => '204 No Content',
            205 => '205 Reset Content',
            206 => '206 Partial Content',
            207 => '207 Multi-Status',
            208 => '208 Already Reported',
            226 => '226 IM Used',
            300 => '300 Multiple Choices',
            301 => '301 Moved Permanently',
            302 => '302 Found',
            303 => '303 See Other',
            304 => '304 Not Modified',
            305 => '305 Use Proxy',
            307 => '307 Temporary Redirect',
            308 => '308 Permanent Redirect',
            400 => '400 Bad Request',
            401 => '401 Unauthorized',
            402 => '402 Payment Required',
            403 => '403 Forbidden',
            404 => '404 Not Found',
            405 => '405 Method Not Allowed',
            406 => '406 Not Acceptable',
            407 => '407 Proxy Authentication Required',
            408 => '408 Request Timeout',
            409 => '409 Conflict',
            410 => '410 Gone',
            411 => '411 Length Required',
            412 => '412 Precondition Failed',
            413 => '413 Payload Too Large',
            414 => '414 URI Too Long',
            415 => '415 Unsupported Media Type',
            416 => '416 Range Not Satisfiable',
            417 => '417 Expectation Failed',
            418 => "418 I'm a teapot",
            421 => '421 Misdirected Request',
            422 => '422 Unprocessable Entity',
            423 => '423 Locked',
            424 => '424 Failed Dependency',
            426 => '426 Upgrade Required',
            451 => '451 Unavailable For Legal Reasons',
            500 => '500 Internal Server Error',
            501 => '501 Not Implemented',
            502 => '502 Bad Gateway',
            503 => '503 Service Unavailable',
            504 => '504 Gateway Timeout',
            505 => '505 HTTP Version Not Supported',
            506 => '506 Variant Also Negotiates',
            507 => '507 Insufficient Storage',
            508 => '508 Loop Detected',
            509 => '509 Bandwidth Limit Exceeded',
            510 => '510 Not Extended',
            511 => '511 Network Authentication Required',
        ];

        private const EMULATED_DNS = [
            'github.com' => [
                ["host" => "github.com", "class" => "IN", "ttl" => 3600, "type" => "SOA", "mname" => "ns1.p16.dynect.net", "rname" => "hostmaster.github.com", "serial" => 1540354846, "refresh" => 3600, "retry" => 600, "expire" => 604800, "minimum-ttl" => 60],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 , "type" => "NS", "target" => "ns-520.awsdns-01.net"],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 , "type" => "NS", "target" => "ns-421.awsdns-52.com"],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 , "type" => "NS", "target" => "ns4.p16.dynect.net"],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 , "type" => "NS", "target" => "ns-1707.awsdns-21.co.uk"],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 , "type" => "NS", "target" => "ns3.p16.dynect.net"],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 , "type" => "NS", "target" => "ns-1283.awsdns-32.org"],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 , "type" => "NS", "target" => "ns2.p16.dynect.net"],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 , "type" => "NS", "target" => "ns1.p16.dynect.net"],
                ["host" => "github.com", "class" => "IN", "ttl" => 60  , "type" => "A", "ip" => "192.30.253.112"],
                ["host" => "github.com", "class" => "IN", "ttl" => 60  , "type" => "A", "ip" => "192.30.253.113"],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 10, "target" => "ALT4.ASPMX.L.GOOGLE.com"],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 1, "target" => "ASPMX.L.GOOGLE.com"],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 5, "target" => "ALT1.ASPMX.L.GOOGLE.com"],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 10, "target" => "ALT3.ASPMX.L.GOOGLE.com"],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 5, "target" => "ALT2.ASPMX.L.GOOGLE.com"],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600, "type" => "TXT", "txt" => "MS=ms44452932", "entries" => ["MS=ms44452932"]],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600, "type" => "TXT", "txt" => "MS=6BF03E6AF5CB689E315FB6199603BABF2C88D805", "entries" => ["MS=6BF03E6AF5CB689E315FB6199603BABF2C88D805"]],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600, "type" => "TXT", "txt" => "v=spf1 ip4:192.30.252.0\/22 ip4:208.74.204.0\/22 ip4:46.19.168.0\/23 include:_spf.google.com include:esp.github.com include:_spf.createsend.com include:mail.zendesk.com include:servers.mcsv.net ~all", "entries" => ["v=spf1 ip4:192.30.252.0\/22 ip4:208.74.204.0\/22 ip4:46.19.168.0\/23 include:_spf.google.com include:esp.github.com include:_spf.createsend.com include:mail.zendesk.com include:servers.mcsv.net ~all"]],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600, "type" => "TXT", "txt" => "docusign=087098e3-3d46-47b7-9b4e-8a23028154cd", "entries" => ["docusign=087098e3-3d46-47b7-9b4e-8a23028154cd"]],
            ],
        ];

        public static $header_raw_arges = [];
        private static $emulated_header = ['http' => ['HTTP/1.1 200 OK']];

        private function __construct()
        {
        }

        public static function initMock()
        {
            self::$header_raw_arges = [];
            self::$emulated_header  = ['http' => ['HTTP/1.1 200 OK']];
        }

        public static function exit($status = null) : void
        {
            throw new ExitException($status);
        }

        public static function die($status = null) : void
        {
            throw new DieException($status);
        }

        public static function header(string $header, bool $replace = true, int $http_response_code = null) : void
        {
            self::$header_raw_arges[] = compact('header', 'replace', 'http_response_code');

            if (\preg_match('/^HTTP\//', $header)) {
                self::$emulated_header['http'] = [$header];
            } elseif ($http_response_code !== null && isset(self::HTTP_STATUS[$http_response_code])) {
                $parts                         = \explode(' ', self::$emulated_header['http'][0], 2);
                self::$emulated_header['http'] = [$parts[0].' '.self::HTTP_STATUS[$http_response_code]];
            }

            if (\strpos($header, ':') !== false) {
                $parts = \explode(':', $header, 2);
                $key   = \strtolower($parts[0]);
                if (!isset(self::$emulated_header[$key])) {
                    self::$emulated_header[$key] = [];
                }
                if ($replace) {
                    self::$emulated_header[$key] = [$header];
                } else {
                    self::$emulated_header[$key][] = $header;
                }
            }
        }

        public static function headers_list() : array
        {
            return Arrays::flatten(\array_values(self::$emulated_header));
        }

        /**
         * Emulate dns_get_record()
         * # $authns, $addtl and $raw emulate currently not supported.
         *
         * @param string $hostname
         * @param integer $type
         * @param array|null $authns
         * @param array|null $addtl
         * @param boolean $raw
         * @return array
         */
        public static function dns_get_record(string $hostname, int $type = DNS_ANY, ?array &$authns = null, ?array &$addtl = null, bool $raw = false) : array
        {
            if (isset(static::EMULATED_DNS[$hostname])) {
                $c = Stream::of(static::EMULATED_DNS[$hostname], true);
                return array_values($c->where(function ($v) use ($type) {
                    $vt = Reflector::get($v, 'type');
                    switch (true) {
                        case DNS_ANY === $type: return true;
                        case DNS_ALL === $type: return true;
                        case DNS_A & $type && $vt === 'A': return true;
                        case DNS_CNAME & $type && $vt === 'CNAME': return true;
                        case DNS_HINFO & $type && $vt === 'HINFO': return true;
                        case DNS_CAA & $type && $vt === 'CAA': return true;
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
        }
    }
}
