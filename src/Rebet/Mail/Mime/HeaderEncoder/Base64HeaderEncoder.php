<?php

namespace Rebet\Mail\Mime\HeaderEncoder;

use Rebet\Tools\Utility\Strings;
use Swift_Mime_HeaderEncoder;

/**
 * Base 64 Header Encode class
 * 
 * It uses mb_encode_mimeheader instead of default encodeString.
 * This class is used to avoid the problem of garbled characters in multi-byte
 * character strings such as Japanese when the subject line is long.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Base64HeaderEncoder implements Swift_Mime_HeaderEncoder
{
    /**
     * Get the name of this encoding scheme.
     * Returns the string 'B'.
     *
     * @return string
     */
    public function getName()
    {
        return 'B';
    }

    /**
     * Takes an unencoded string and produces a Base64 encoded string from it.
     *
     * It uses mb_encode_mimeheader instead of default encodeString.
     * This class is used to avoid the problem of garbled characters in multi-byte
     * character strings such as Japanese when the subject line is long.
     *
     * @param string $string          string to encode
     * @param int    $firstLineOffset (do not use)
     * @param int    $maxLineLength   (do not use)
     * @param string $charset         charset (default: 'UTF-8')
     *
     * @return string
     */
    public function encodeString($string, $firstLineOffset = 0, $maxLineLength = 0, $charset = 'UTF-8')
    {
        return preg_replace(["/ *=\\?{$charset}\\?{$this->getName()}\\?/i", "/\\?=/"], '', mb_encode_mimeheader($string, $charset, $this->getName(), "\r\n"));
    }

    /**
     * Does nothing.
     */
    public function charsetChanged($charset)
    {
    }
}
