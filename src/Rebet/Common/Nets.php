<?php
namespace Rebet\Common;

/**
 * Nets Utility Class
 *
 * A class that collects simple utility methods related to the network.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Nets
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * Convert binary data to a string that can be used for URL.
     * Note: Returns a character string that replaces Base64 "URL Unsafe" character "+ / =" with URL Safe character "._-".
     *
     * @param mixed $byte
     * @return string
     */
    public static function encodeBase64Url($byte) : string
    {
        return strtr(base64_encode($byte), '+/=', '._-');
    }
    
    /**
     * Convert available strings in URL to binary data.
     * Note: Restore the data from the character string "+ / =" of Base64 "URL Unsafe" replaced by URL Safe character "._-".
     *
     * @param string $encoded
     * @return mixed
     */
    public static function decodeBase64Url(string $encoded)
    {
        return base64_decode(strtr($encoded, '._-', '+/='));
    }

    /**
     * Get page data of specified URL with file_get_contents.
     *
     * @param string $url
     * @return mixed
     */
    public static function urlGetContents(string $url)
    {
        return file_get_contents($url, false, stream_context_create([
            'http' => ['ignore_errors' => true],
            'ssl'  => [
                'verify_peer'      => false,
                'verify_peer_name' => false
            ],
        ]));
    }
}
