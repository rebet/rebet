<?php

/**
 * テスト用モッククラスを定義
 */
namespace Rebet\Common {
    use Rebet\Tests\DieException;
    use Rebet\Tests\ExitException;
    use Rebet\Common\Arrays;

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
                $parts = \explode(' ', self::$emulated_header['http'][0], 2);
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
            } else {
                // Do nothing.
            }
        }

        public static function headers_list() : array
        {
            return Arrays::flatten(\array_values(self::$emulated_header));
        }
    }
}
