<?php
namespace Rebet\Tools;

/**
 * System Class
 *
 * It is a class aimed to make it possible to hook on unit tests about linguistic structures such as exit / die
 * and functions that operate only with SAPI such as header.
 *
 * This class can be replaced with mock class in unit testing.
 * @see tests/mocks.php
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class System
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * The wrapper method of exit().
     *
     * @param int|string|null (default: null)
     */
    public static function exit($status = null) : void
    {
        if ($status === null) {
            exit();
        }
        exit($status);
    }

    /**
     * The wrapper method of die().
     *
     * @param int|string|null (default: null)
     */
    public static function die($status = null) : void
    {
        if ($status === null) {
            die();
        }
        die($status);
    }

    /**
     * The wrapper method of header().
     *
     * @param string $header
     * @param bool $replace (default: true)
     * @param int $http_response_code (default: null)
     */
    public static function header(string $header, bool $replace = true, int $http_response_code = null) : void
    {
        \header($header, $replace, $http_response_code);
    }

    /**
     * The wrapper method of headers_list().
     *
     * @return array
     */
    public static function headers_list() : array
    {
        return \headers_list();
    }

    /**
     * The wrapper method of dns_get_record().
     *
     * @param string $hostname
     * @param integer $type (default: DNS_ANY)
     * @param array|null $authns (default: null)
     * @param array|null $addtl (default: null)
     * @param boolean $raw (default: false)
     * @return array
     */
    public static function dns_get_record(string $hostname, int $type = DNS_ANY, ?array &$authns = null, ?array &$addtl = null, bool $raw = false) : array
    {
        return dns_get_record($hostname, $type, $authns, $addtl, $raw);
    }
}
