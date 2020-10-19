<?php
namespace Rebet\Mail\Mime;

use DateTimeInterface;
use Rebet\Mail\Mail;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Utility\Arrays;
use Swift_Mime_Header;
use Swift_Mime_SimpleHeaderSet;

/**
 * Header Set class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class HeaderSet
{
    /**
     * @var Mail
     */
    protected $mail;

    /**
     * @var Swift_Mime_SimpleHeaderSet
     */
    protected $headers;

    /**
     * Create Header Set
     *
     * @param Mail $mail
     */
    public function __construct(Mail $mail)
    {
        $this->mail    = $mail;
        $this->headers = $mail->message()->getHeaders();
    }

    /**
     * End the header setting method chain, then return mail object and restart mail setting method chain.
     *
     * @return Mail
     */
    public function endheader() : Mail
    {
        return $this->mail;
    }

    /**
     * This is shorthand method for add TEXT, DATE and PARAMETERIZED type header.
     * NOTE: This method can not set null value to header, so if the value can be null then you should use addXxxxHeader() method.
     *
     * @param string $name
     * @param string|DateTimeInterface $value
     * @param array|null $params for add PARAMETERIZED header syntax. (default: null for depend on value type DATE or TEXT syntax).
     * @return self
     */
    public function add(string $name, $value, $params = null) : self
    {
        if ($value === null) {
            throw new LogicException("Invalid value(=null) was given for '{$name}' header, if the value can be null then you should use addXxxxHeader() method.");
        }

        switch (true) {
            case is_array($params):                   $this->headers->addParameterizedHeader($name, $value, $params); break;
            case $value instanceof DateTimeInterface: $this->headers->addDateHeader($name, $value); break;
            default:                                  $this->headers->addTextHeader($name, (string)$value); break;
        }

        return $this;
    }

    /**
     * Add a new ID header like Message-ID or Content-ID.
     *
     * @param string $name
     * @param string|array|null $ids format of `id-left "@" id-right` defined in RFC 2822
     * @return self
     */
    public function addIdHeader(string $name, $ids = null) : self
    {
        $this->headers->addIdHeader($name, $ids);
        return $this;
    }

    /**
     * Add a new Mailbox Header with a list of $addresses.
     *
     * @param string $name
     * @param string|array|null $addresses can be 'foo@bar.com', 'Foo <foo@bar.com>', ['foo@bar.com' => 'Foo'] or ['foo@bar.com' => 'Foo', 'baz@bar.com', 'Qux <qux@bar.com>', ...]
     * @return self
     */
    public function addMailboxHeader(string $name, $addresses = null) : self
    {
        $this->headers->addMailboxHeader($name, Mail::resolve($addresses));
        return $this;
    }

    /**
     * Add a new Path header with an address (path) in it.
     *
     * @param string $name
     * @param string|array|null $address can be 'foo@bar.com', 'Foo <foo@bar.com>' or ['foo@bar.com' => 'Foo'] but it is just used email address part. (default: null)
     * @return self
     */
    public function addPathHeader(string $name, $address = null) : self
    {
        $this->headers->addPathHeader($name, array_key_first(Mail::resolve($address)));
        return $this;
    }

    /**
     * Add a new Parameterized Header with $name, $value and $params.
     *
     * @param string $name
     * @param string|null $value (default: null)
     * @param array $params (default: [])
     * @return self
     */
    public function addParameterizedHeader(string $name, ?string $value = null, array $params = []) : self
    {
        $this->headers->addParameterizedHeader($name, $value, $params);
        return $this;
    }

    /**
     * Add a new basic text header with $name and $value.
     *
     * @param string $name
     * @param string|null $value (default: null)
     * @return self
     */
    public function addTextHeader(string $name, ?string $value = null) : self
    {
        $this->headers->addTextHeader($name, $value);
        return $this;
    }

    /**
     * Add a new Date header using $dateTime.
     *
     * @param string $name
     * @param DateTimeInterface|null $datetime (default: null)
     * @return self
     */
    public function addDateHeader(string $name, ?DateTimeInterface $datetime = null) : self
    {
        $this->headers->addDateHeader($name, $datetime);
        return $this;
    }

    /**
     * Set the given header to this header set.
     *
     * @param Swift_Mime_Header $header
     * @param int $index (default: 0)
     * @return self
     */
    public function set(Swift_Mime_Header $header, int $index = 0) : self
    {
        $this->headers->set($header, $index);
        return $this;
    }

    /**
     * Returns true if at least one header with the given $name exists.
     * If multiple headers match, the actual one may be specified by $index.
     *
     * @param string $name
     * @param int $index (default : 0)
     * @return bool
     */
    public function has(string $name, int $index = 0) : bool
    {
        return $this->headers->has($name, $index);
    }

    /**
     * Get the given name header(s).
     *
     * @param string $name
     * @return Swift_Mime_Header|Swift_Mime_Header[]
     */
    public function get(string $name)
    {
        return Arrays::peel($this->headers->getAll($name)) ;
    }

    /**
     * Get all header(s).
     *
     * @return Swift_Mime_Header[]
     */
    public function all() : array
    {
        return $this->headers->getAll();
    }

    /**
     * Get the all header name.
     *
     * @return string[]
     */
    public function list() : array
    {
        return $this->headers->listAll();
    }

    /**
     * Remove given name header(s).
     *
     * @param string $name
     * @param int|null $index (default: null for remove given name all headers)
     * @return self
     */
    public function remove(string $name, ?int $index = null) : self
    {
        if ($index === null) {
            $this->headers->removeAll($name);
        } else {
            $this->headers->remove($name, $index);
        }
        return $this;
    }

    /**
     * Define a list of Header names as an array in the correct order.
     * These Headers will be output in the given order where present.
     *
     * @param string ...$sequence
     * @return self
     */
    public function defineOrdering(string ...$sequence) : self
    {
        $this->headers->defineOrdering($sequence);
        return $this;
    }

    /**
     * Set a list of header names which must always be displayed when set.
     * Usually headers without a field value won't be output unless set here.
     *
     * @param string ...$names
     * @return self
     */
    public function setAlwaysDisplayed(string ...$names) : self
    {
        $this->headers->setAlwaysDisplayed($names);
        return $this;
    }

    /**
     * Returns a string with a representation of all headers.
     *
     * @return string
     */
    public function toString() : string
    {
        return $this->headers->toString();
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Get a loggable string.
     *
     * @return string
     */
    public function toReadableString() : string
    {
        return static::convertToReadableString($this->headers);
    }

    /**
     * Get a loggable string out of a Swiftmailer HeaderSet.
     *
     * @param Swift_Mime_SimpleHeaderSet $headers
     * @return string
     */
    public static function convertToReadableString(Swift_Mime_SimpleHeaderSet $headers) : string
    {
        $headers = $headers instanceof HeaderSet ? $headers->headers : $headers ;
        $string  = '';
        foreach ($headers->listAll() as $name) {
            foreach ($headers->getAll($name) as $header) {
                if (Reflector::invoke($headers, 'isDisplayed', [$header], true) || '' != $header->getFieldBody()) {
                    $string .= mb_decode_mimeheader($header->toString())."\r\n";
                }
            }
        }
        return $string;
    }

    /**
     * Get the Swift Header Set of origin resource of this header set.
     *
     * @return Swift_Mime_SimpleHeaderSet
     */
    public function toSwiftHeaderSet() : Swift_Mime_SimpleHeaderSet
    {
        return $this->headers;
    }
}
