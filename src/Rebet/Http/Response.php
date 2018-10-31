<?php
namespace Rebet\Http;

use Symfony\Component\HttpFoundation\Request;

/**
 * Response interface
 *
 * This interface covered Symfony\Component\HttpFoundation\Response of symfony/http-foundation ver 4.1.
 *
 * @see https://github.com/symfony/http-foundation/blob/master/Response.php
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Response
{
    /******************************************************
     * Symfony's interface
     ******************************************************/
    public static function create($content = '', $status = 200, $headers = []);

    public function __toString();

    public function __clone();

    public function prepare(Request $request);

    public function sendHeaders();

    public function sendContent();

    public function send();

    public function setContent($content);

    public function getContent();

    public function setProtocolVersion(string $version);

    public function getProtocolVersion() : string;

    public function setStatusCode(int $code, $text = null);

    public function getStatusCode() : int;

    public function setCharset(string $charset);

    public function getCharset() : ?string;

    public function isCacheable() : bool;

    public function isFresh() : bool;

    public function isValidateable() : bool;

    public function setPrivate();

    public function setPublic();

    public function setImmutable(bool $immutable = true);

    public function isImmutable() : bool;

    public function mustRevalidate() : bool;

    public function getDate() : ?\DateTimeInterface;

    public function setDate(\DateTimeInterface $date);

    public function getAge() : int;

    public function expire();

    public function getExpires() : ?\DateTimeInterface;

    public function setExpires(\DateTimeInterface $date = null);

    public function getMaxAge() : ?int;

    public function setMaxAge(int $value);

    public function setSharedMaxAge(int $value);

    public function getTtl() : ?int;

    public function setTtl(int $seconds);

    public function setClientTtl(int $seconds);

    public function getLastModified() : ?\DateTimeInterface;

    public function setLastModified(\DateTimeInterface $date = null);

    public function getEtag() : ?string;

    public function setEtag(string $etag = null, bool $weak = false);

    public function setCache(array $options);

    public function setNotModified();

    public function hasVary() : bool;

    public function getVary() : array;

    public function setVary($headers, bool $replace = true);

    public function isNotModified(Request $request) : bool;

    public function isInvalid() : bool;

    public function isInformational() : bool;

    public function isSuccessful() : bool;

    public function isRedirection() : bool;

    public function isClientError() : bool;

    public function isServerError() : bool;

    public function isOk() : bool;

    public function isForbidden() : bool;

    public function isNotFound() : bool;

    public function isRedirect(string $location = null) : bool;

    public function isEmpty() : bool;

    public static function closeOutputBuffers(int $targetLevel, bool $flush);

    /******************************************************
     * Rebet's additional interface
     * implemented by Rebet\Http\Respondable trait.
     ******************************************************/
    public function getHeader(string $key);

    public function setHeader(string $key, $values, bool $replace = true) : Response;
}
