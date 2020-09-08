<?php
namespace Rebet\Http;

use Rebet\Http\Cookie\Cookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * Response interface
 *
 * This interface covered Symfony\Component\HttpFoundation\Response of symfony/http-foundation ver 5.1.
 * The Symfony's interface signature and comment text are borrowed from symfony/http-foundation ver 5.1. with some modifications.
 *
 * @see https://github.com/symfony/http-foundation/blob/5.1/Response.php
 * @see https://github.com/symfony/http-foundation/blob/5.1/LICENSE
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface Response
{
    /******************************************************
     * Rebet's additional interface
     * implemented by Rebet\Http\Response\Respondable trait.
     ******************************************************/

    /**
     * Get a header from the Response.
     *
     * @param string $key
     * @param bool $first (default: false)
     * @return string|string[]|null
     */
    public function getHeader(string $key);

    /**
     * Set a header on the Response.
     *
     * @param string $key
     * @param array|string $values
     * @param boolean $replace (default: true)
     * @return self
     */
    public function setHeader(string $key, $values, bool $replace = true) : self;

    /**
     * Get the cookie of given name.
     *
     * @param string $name
     * @param string|null $path filter can contains shell's wildcard (default: '*')
     * @param string|null $domain filter can contains shell's wildcard (default: '*')
     * @return Cookie|array|null
     */
    public function getCookie(string $name, ?string $path = '*', ?string $domain = '*');

    /**
     * Set the cookie.
     *
     * @param Cookie $cookie
     * @return self
     */
    public function setCookie(Cookie $cookie) : self;

    /******************************************************
     * Symfony's interface
     ******************************************************/

    /**
     * Returns the Response as an HTTP string.
     *
     * The string representation of the Response is the same as the
     * one that will be sent to the client only if the prepare() method
     * has been called before.
     *
     * @return string The Response as an HTTP string
     * @see prepare()
     */
    public function __toString();

    /**
     * Clones the current Response instance.
     */
    public function __clone();

    /**
     * Prepares the Response before it is sent to the client.
     *
     * This method tweaks the Response to ensure that it is
     * compliant with RFC 2616. Most of the changes are based on
     * the Request that is "associated" with this Response.
     *
     * @return self
     */
    public function prepare(Request $request);

    /**
     * Sends HTTP headers.
     *
     * @return self
     */
    public function sendHeaders();

    /**
     * Sends content for the current web response.
     *
     * @return self
     */
    public function sendContent();

    /**
     * Sends HTTP headers and content.
     *
     * @return self
     */
    public function send();

    /**
     * Sets the response content.
     *
     * Valid types are strings, numbers, null, and objects that implement a __toString() method.
     *
     * @param string|null $content Content that can be cast to string
     * @return self
     * @throws \UnexpectedValueException
     */
    public function setContent(?string $content);

    /**
      * Gets the current response content.
      *
      * @return string Content
      */
    public function getContent();

    /**
     * Sets the HTTP protocol version (1.0 or 1.1).
     *
     * @return self
     * @final
     */
    public function setProtocolVersion(string $version);

    /**
     * Gets the HTTP protocol version.
     *
     * @final
     */
    public function getProtocolVersion() : string;

    /**
     * Sets the response status code.
     *
     * If the status text is null it will be automatically populated for the known
     * status codes and left empty otherwise.
     *
     * @return self
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     * @final
     */
    public function setStatusCode(int $code, $text = null);

    /**
     * Retrieves the status code for the current web response.
     *
     * @final
     */
    public function getStatusCode() : int;

    /**
     * Sets the response charset.
     *
     * @return self
     * @final
     */
    public function setCharset(string $charset);

    /**
     * Retrieves the response charset.
     *
     * @final
     */
    public function getCharset() : ?string;

    /**
     * Returns true if the response may safely be kept in a shared (surrogate) cache.
     *
     * Responses marked "private" with an explicit Cache-Control directive are
     * considered uncacheable.
     *
     * Responses with neither a freshness lifetime (Expires, max-age) nor cache
     * validator (Last-Modified, ETag) are considered uncacheable because there is
     * no way to tell when or how to remove them from the cache.
     *
     * Note that RFC 7231 and RFC 7234 possibly allow for a more permissive implementation,
     * for example "status codes that are defined as cacheable by default [...]
     * can be reused by a cache with heuristic expiration unless otherwise indicated"
     * (https://tools.ietf.org/html/rfc7231#section-6.1)
     *
     * @final
     */
    public function isCacheable() : bool;

    /**
     * Returns true if the response is "fresh".
     *
     * Fresh responses may be served from cache without any interaction with the
     * origin. A response is considered fresh when it includes a Cache-Control/max-age
     * indicator or Expires header and the calculated age is less than the freshness lifetime.
     *
     * @final
     */
    public function isFresh() : bool;

    /**
     * Returns true if the response includes headers that can be used to validate
     * the response with the origin server using a conditional GET request.
     *
     * @final
     */
    public function isValidateable() : bool;

    /**
     * Marks the response as "private".
     *
     * It makes the response ineligible for serving other clients.
     *
     * @return self
     * @final
     */
    public function setPrivate();

    /**
     * Marks the response as "public".
     *
     * It makes the response eligible for serving other clients.
     *
     * @return self
     * @final
     */
    public function setPublic();

    /**
     * Marks the response as "immutable".
     *
     * @return self
     * @final
     */
    public function setImmutable(bool $immutable = true);

    /**
     * Returns true if the response is marked as "immutable".
     *
     * @final
     */
    public function isImmutable() : bool;

    /**
     * Returns true if the response must be revalidated by caches.
     *
     * This method indicates that the response must not be served stale by a
     * cache in any circumstance without first revalidating with the origin.
     * When present, the TTL of the response should not be overridden to be
     * greater than the value provided by the origin.
     *
     * @final
     */
    public function mustRevalidate() : bool;

    /**
     * Returns the Date header as a DateTime instance.
     *
     * @throws \RuntimeException When the header is not parseable
     *
     * @final
     */
    public function getDate() : ?\DateTimeInterface;

    /**
     * Sets the Date header.
     *
     * @return self
     * @final
     */
    public function setDate(\DateTimeInterface $date);

    /**
     * Returns the age of the response in seconds.
     *
     * @final
     */
    public function getAge() : int;

    /**
     * Marks the response stale by setting the Age header to be equal to the maximum age of the response.
     *
     * @return self
     */
    public function expire();

    /**
     * Returns the value of the Expires header as a DateTime instance.
     *
     * @final
     */
    public function getExpires() : ?\DateTimeInterface;

    /**
     * Sets the Expires HTTP header with a DateTime instance.
     *
     * Passing null as value will remove the header.
     *
     * @return self
     * @final
     */
    public function setExpires(\DateTimeInterface $date = null);

    /**
     * Returns the number of seconds after the time specified in the response's Date
     * header when the response should no longer be considered fresh.
     *
     * First, it checks for a s-maxage directive, then a max-age directive, and then it falls
     * back on an expires header. It returns null when no maximum age can be established.
     *
     * @final
     */
    public function getMaxAge() : ?int;

    /**
     * Sets the number of seconds after which the response should no longer be considered fresh.
     *
     * This methods sets the Cache-Control max-age directive.
     *
     * @return self
     * @final
     */
    public function setMaxAge(int $value);

    /**
     * Sets the number of seconds after which the response should no longer be considered fresh by shared caches.
     *
     * This methods sets the Cache-Control s-maxage directive.
     *
     * @return self
     * @final
     */
    public function setSharedMaxAge(int $value);

    /**
     * Returns the response's time-to-live in seconds.
     *
     * It returns null when no freshness information is present in the response.
     *
     * When the responses TTL is <= 0, the response may not be served from cache without first
     * revalidating with the origin.
     *
     * @final
     */
    public function getTtl() : ?int;

    /**
     * Sets the response's time-to-live for shared caches in seconds.
     *
     * This method adjusts the Cache-Control/s-maxage directive.
     *
     * @return self
     * @final
     */
    public function setTtl(int $seconds);

    /**
     * Sets the response's time-to-live for private/client caches in seconds.
     *
     * This method adjusts the Cache-Control/max-age directive.
     *
     * @return self
     * @final
     */
    public function setClientTtl(int $seconds);

    /**
     * Returns the Last-Modified HTTP header as a DateTime instance.
     *
     * @throws \RuntimeException When the HTTP header is not parseable
     * @final
     */
    public function getLastModified() : ?\DateTimeInterface;

    /**
     * Sets the Last-Modified HTTP header with a DateTime instance.
     *
     * Passing null as value will remove the header.
     *
     * @return self
     * @final
     */
    public function setLastModified(\DateTimeInterface $date = null);

    /**
     * Returns the literal value of the ETag HTTP header.
     *
     * @final
     */
    public function getEtag() : ?string;

    /**
     * Sets the ETag value.
     *
     * @param string|null $etag The ETag unique identifier or null to remove the header
     * @param bool        $weak Whether you want a weak ETag or not
     * @return self
     * @final
     */
    public function setEtag(string $etag = null, bool $weak = false);

    /**
     * Sets the response's cache headers (validation and/or expiration).
     *
     * Available options are: etag, last_modified, max_age, s_maxage, private, public and immutable.
     *
     * @return self
     * @throws \InvalidArgumentException
     * @final
     */
    public function setCache(array $options);

    /**
     * Modifies the response so that it conforms to the rules defined for a 304 status code.
     *
     * This sets the status, removes the body, and discards any headers
     * that MUST NOT be included in 304 responses.
     *
     * @return self
     * @see http://tools.ietf.org/html/rfc2616#section-10.3.5
     * @final
     */
    public function setNotModified();

    /**
     * Returns true if the response includes a Vary header.
     *
     * @final
     */
    public function hasVary() : bool;

    /**
     * Returns an array of header names given in the Vary header.
     *
     * @final
     */
    public function getVary() : array;

    /**
     * Sets the Vary header.
     *
     * @param string|array $headers
     * @param bool         $replace Whether to replace the actual value or not (true by default)
     * @return self
     * @final
     */
    public function setVary($headers, bool $replace = true);

    /**
     * Determines if the Response validators (ETag, Last-Modified) match
     * a conditional value specified in the Request.
     *
     * If the Response is not modified, it sets the status code to 304 and
     * removes the actual content by calling the setNotModified() method.
     *
     * @return bool true if the Response validators match the Request, false otherwise
     * @final
     */
    public function isNotModified(Request $request) : bool;

    /**
     * Is response invalid?
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     * @final
     */
    public function isInvalid() : bool;

    /**
     * Is response informative?
     *
     * @final
     */
    public function isInformational() : bool;

    /**
     * Is response successful?
     *
     * @final
     */
    public function isSuccessful() : bool;

    /**
     * Is the response a redirect?
     *
     * @final
     */
    public function isRedirection() : bool;

    /**
     * Is there a client error?
     *
     * @final
     */
    public function isClientError() : bool;

    /**
     * Was there a server side error?
     *
     * @final
     */
    public function isServerError() : bool;

    /**
     * Is the response OK?
     *
     * @final
     */
    public function isOk() : bool;

    /**
     * Is the response forbidden?
     *
     * @final
     */
    public function isForbidden() : bool;

    /**
     * Is the response a not found error?
     *
     * @final
     */
    public function isNotFound() : bool;

    /**
     * Is the response a redirect of some form?
     *
     * @final
     */
    public function isRedirect(string $location = null) : bool;

    /**
     * Is the response empty?
     *
     * @final
     */
    public function isEmpty() : bool;

    /**
     * Cleans or flushes output buffers up to target level.
     *
     * Resulting level can be greater than target level if a non-removable buffer has been encountered.
     *
     * @final
     */
    public static function closeOutputBuffers(int $targetLevel, bool $flush);

    /**
     * Marks a response as safe according to RFC8674.
     *
     * @see https://tools.ietf.org/html/rfc8674
     */
    public function setContentSafe(bool $safe = true) : void ;
}
