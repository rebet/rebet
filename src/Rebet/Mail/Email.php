<?php
declare(strict_types=1);

namespace Rebet\Mail;

use Html2Text\Html2Text;
use Override;
use Rebet\Event\Event;
use Rebet\Log\Log;
use Rebet\Mail\Transport\InMemoryTransport;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Config\Exception\ConfigNotDefineException;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Utility\Env;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Mime\Exception\LogicException;
use Symfony\Component\Mime\Header\AbstractHeader;
use Symfony\Component\Mime\Header\DateHeader;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Header\IdentificationHeader;
use Symfony\Component\Mime\Header\MailboxHeader;
use Symfony\Component\Mime\Header\MailboxListHeader;
use Symfony\Component\Mime\Header\ParameterizedHeader;
use Symfony\Component\Mime\Header\PathHeader;
use Symfony\Component\Mime\Header\UnstructuredHeader;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\AlternativePart;
use Symfony\Component\Mime\Part\Multipart\MixedPart;
use Symfony\Component\Mime\Part\Multipart\RelatedPart;
use Symfony\Component\Mime\Part\TextPart;

/**
 * Email Class
 *
 * An extension of Symfony's Email that adds config-driven mailer/transport resolution
 * (see {@see mailer()}, {@see transport()}) and configurable header word-encoding applied
 * at {@see build()}/{@see send()} time (see 'Email.encodes' config).
 *
 * Function generateBody() implementation is borrowed from symfony/mime ver 7.4 with some modifications.
 * The main modification is that configable encodes for header and body can be used instead of hardcoded Quoted-Printable encoder.
 *
 * @see https://github.com/symfony/mime/blob/7.4/Email.php
 * @see https://github.com/symfony/mime/blob/7.4/LICENSE
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2010-present Fabien Potencier
 * @copyright Copyright (c) 2026 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Email extends SymfonyEmail
{
    use Configurable;

    /**
     * @return array<string, mixed>
     */
    public static function defaultConfig()
    {
        return [
            'default_mailer' => 'main',
            'mailers'        => [
                'main' => [
                    'transport' => [
                        '@factory'   => Transport::class."::fromDsn",
                        'dsn'        => Env::get('MAILER_DSN', 'null://null'),
                        'dispatcher' => null, // Instantiable class name of EventDispatcherInterface implementation [ex: Event::psrDispatcher()] or null to disable.
                        'client'     => null, // Instantiable class name of HttpClientInterface implementation, or null to disable.
                        'logger'     => null, // Instantiable class name of LoggerInterface implementation [ex: Log::channel()->driver()] or null to disable.
                    ],
                    'bus'        => null, // Instantiable class name of MessageBusInterface implementation, or null to disable.
                    'dispatcher' => null, // Instantiable class name of EventDispatcherInterface implementation [ex: Event::psrDispatcher()] or null to disable.
                ],
                'unittest' => [
                    'transport' => [
                        '@factory'   => InMemoryTransport::class,
                        'dispatcher' => null, // Instantiable class name of EventDispatcherInterface implementation [ex: Event::psrDispatcher()] or null to disable.
                        'logger'     => null, // Instantiable class name of LoggerInterface implementation [ex: Log::channel('test')->driver()] or null to disable.
                    ],
                    'bus'        => null, // Instantiable class name of MessageBusInterface implementation, or null to disable.
                    'dispatcher' => null, // Instantiable class name of EventDispatcherInterface implementation [ex: Event::psrDispatcher()] or null to disable.
                ],
            ],
            'encodes' => [
                'header' => 'base64', // 'quoted-printable', 'base64'
                'body'   => 'base64', // 'quoted-printable', 'base64', '8bit'
            ],
            'html2text_generator' => fn (string $body) => (new Html2Text($body, ['width' => 0]))->getText(),
        ];
    }

    /**
     * Cache of instantiated Mailer, keyed by mailer name configured in 'Email.mailers'.
     *
     * @var array<string, Mailer>
     */
    protected static $mailers = [];


    /**
     * Cache of the body generated by {@see generateBody()}, invalidated by {@see text()},
     * {@see html()} and {@see addPart()}.
     *
     * Used to avoid a wrong body hash in DKIM signatures with multiple parts (e.g. HTML + TEXT)
     * due to multiple boundaries.
     *
     * @var AbstractPart|null
     */
    protected AbstractPart|null $cachedBody = null;

    /**
     * Create a new Email.
     *
     * @param Headers|null $headers of this email (default: null for new empty Headers)
     * @param AbstractPart|null $body of this email (default: null for none)
     */
    public function __construct(Headers|null $headers = null, private AbstractPart|null $body = null)
    {
        parent::__construct($headers, $body);
    }

    /**
     * Get the mailer for given mailer.
     *
     * @param string|null $mailer name that configured in 'Email.mailers'. (default: null to use 'Email.default_mailer')
     * @return Mailer
     * @throws ConfigNotDefineException
     */
    public static function mailer(string|null $mailer = null) : Mailer
    {
        $mailer = $mailer ?? static::config('default_mailer');

        if (isset(static::$mailers[$mailer])) {
            return static::$mailers[$mailer];
        }

        if (!static::config("mailers.{$mailer}")) {
            throw new ConfigNotDefineException("Mailer '{$mailer}' is not configured.");
        }

        $transport  = Reflector::instantiate(static::config("mailers.{$mailer}.transport"));
        $dispatcher = Reflector::instantiate(static::config("mailers.{$mailer}.dispatcher", false));
        $bus        = Reflector::instantiate(static::config("mailers.{$mailer}.bus", false));

        return static::$mailers[$mailer] = new Mailer($transport, $bus, $dispatcher);
    }

    /**
     * Get the transport for given mailer.
     *
     * @param string|null $mailer name that configured in 'Email.mailers'. (default: null to use 'Email.default_mailer')
     * @return TransportInterface
     * @throws ConfigNotDefineException
     */
    public static function transport(string|null $mailer = null) : TransportInterface
    {
        return Reflector::get(static::mailer($mailer), 'transport', accessible: true);
    }

    /**
     * Reset the Email context
     *
     * @return void
     */
    public static function reset() : void
    {
        static::$mailers = [];
    }

    /**
     * Generate the text body from html body.
     *
     * @param callable|null $generator (default: null to use 'Email.html2text_generator' config)
     * @return static
     */
    public function generateTextBodyFromHtml(callable|null $generator = null) : static
    {
        $generator = $generator ?? static::config('html2text_generator', false, null);
        return $this->text(Reflector::evaluate($generator, [$this->getHtmlBody()]));
    }

    /**
     * {@inheritDoc}
     *
     * @param resource|string|null $body
     * @param string $charset of $body
     * @return $this
     */
    #[Override]
    public function text($body, string $charset = 'utf-8') : static
    {
        $this->cachedBody = null;
        return parent::text($body, $charset);
    }

    /**
     * {@inheritDoc}
     *
     * @param resource|string|null $body
     * @param string $charset of $body
     * @return $this
     */
    #[Override]
    public function html($body, string $charset = 'utf-8') : static
    {
        $this->cachedBody = null;
        return parent::html($body, $charset);
    }

    /**
     * {@inheritDoc}
     *
     * @param DataPart $part
     * @return $this
     */
    #[Override]
    public function addPart(DataPart $part) : static
    {
        $this->cachedBody = null;
        return parent::addPart($part);
    }

    /**
     * {@inheritDoc}
     *
     * @return AbstractPart
     */
    #[Override]
    public function getBody() : AbstractPart
    {
        if (null !== $body = Message::getBody()) {
            return $body;
        }

        return $this->generateBody();
    }

    /**
     * Generates an AbstractPart based on the raw body of a message.
     *
     * The most "complex" part generated by this method is when there is text and HTML bodies
     * with related images for the HTML part and some attachments:
     *
     * multipart/mixed
     *         |
     *         |------------> multipart/related
     *         |                      |
     *         |                      |------------> multipart/alternative
     *         |                      |                      |
     *         |                      |                       ------------> text/plain (with content)
     *         |                      |                      |
     *         |                      |                       ------------> text/html (with content)
     *         |                      |
     *         |                       ------------> image/png (with content)
     *         |
     *          ------------> application/pdf (with content)
     *
     * @return AbstractPart
     * @throws LogicException if this email has neither a text/HTML body nor any attachments
     */
    protected function generateBody() : AbstractPart
    {
        if (null !== $this->cachedBody) {
            return $this->cachedBody;
        }

        $this->ensureBodyValid();

        [$htmlPart, $otherParts, $relatedParts] = $this->prepareParts();

        $part = null === $this->getTextBody() ? null : new TextPart($this->getTextBody(), $this->getTextCharset(), 'plain', static::config('encodes.body'));
        if (null !== $htmlPart) {
            if (null !== $part) {
                $part = new AlternativePart($part, $htmlPart);
            } else {
                $part = $htmlPart;
            }
        }

        if ($relatedParts) {
            $part = new RelatedPart($part, ...$relatedParts);
        }

        if ($otherParts) {
            if ($part) {
                $part = new MixedPart($part, ...$otherParts);
            } else {
                $part = new MixedPart(...$otherParts);
            }
        }

        return $this->cachedBody = $part;
    }

    /**
     * Ensure this email has a text or an HTML part or attachments before {@see generateBody()}
     * builds its body part(s) from them.
     *
     * @return void
     * @throws LogicException if this email has neither a text/HTML body nor any attachments
     */
    protected function ensureBodyValid() : void
    {
        if (null === $this->getTextBody() && null === $this->getHtmlBody() && !$this->getAttachments() && null === Message::getBody()) {
            throw new LogicException('A message must have a text or an HTML part or attachments.');
        }
    }

    /**
     * Build the HTML part (associating any attachment referenced via a 'cid:' URL as a related
     * part) and split the remaining attachments into related/other parts, for use by
     * {@see generateBody()}.
     *
     * @return array{0: TextPart|null, 1: array<int, DataPart>, 2: array<int, DataPart>} [$htmlPart, $otherParts, $relatedParts]
     */
    protected function prepareParts() : array|null
    {
        $names    = [];
        $htmlPart = null;
        $html     = $this->getHtmlBody();
        if (null !== $html) {
            $htmlPart = new TextPart($html, $this->getHtmlCharset(), 'html', static::config('encodes.body'));
            $html     = $htmlPart->getBody();

            $regexes = [
                '<img\s+[^>]*src\s*=\s*(?:([\'"])cid:(.+?)\\1|cid:([^>\s]+))',
                '<\w+\s+[^>]*background\s*=\s*(?:([\'"])cid:(.+?)\\1|cid:([^>\s]+))',
            ];
            $tmpMatches = [];
            foreach ($regexes as $regex) {
                preg_match_all('/'.$regex.'/i', $html, $tmpMatches);
                $names = array_merge($names, $tmpMatches[2], $tmpMatches[3]);
            }
            $names = array_filter(array_unique($names));
        }

        $otherParts = $relatedParts = [];
        foreach ($this->getAttachments() as $part) {
            foreach ($names as $name) {
                if ($name !== $part->getName() && (!$part->hasContentId() || $name !== $part->getContentId())) {
                    continue;
                }
                if (isset($relatedParts[$name])) {
                    continue 2;
                }

                if ($name !== $part->getContentId()) {
                    $html = str_replace('cid:'.$name, 'cid:'.$part->getContentId(), $html);
                }
                $relatedParts[$name] = $part;
                $part->setName($part->getName() ?? $part->getContentId())->asInline();

                continue 2;
            }

            $otherParts[] = $part;
        }
        if (null !== $htmlPart) {
            $htmlPart = new TextPart($html, $this->getHtmlCharset(), 'html', static::config('encodes.body'));
        }

        return [$htmlPart, $otherParts, array_values($relatedParts)];
    }

    /**
     * {@inheritDoc}
     *
     * Ensures {@see prepareHeaders()} has been applied (e.g. a default Date header) before
     * returning the prepared headers.
     *
     * @return Headers
     */
    #[Override]
    public function getPreparedHeaders() : Headers
    {
        $this->prepareHeaders();
        return parent::getPreparedHeaders();
    }

    /**
     * Fill in headers that must have a value but have not been set explicitly yet
     * (currently just the Date header, defaulted to the current date/time).
     *
     * @return void
     */
    protected function prepareHeaders() : void
    {
        if ($this->getDate() === null) {
            $this->date(DateTime::now());
        }
    }

    /**
     * Build a ready-to-send clone of this email with its headers word-encoded as needed.
     *
     * Non-ASCII header values that do not match the RFC 2822 'phrase' grammar (see
     * {@see needEncode()}) are encoded into RFC 2047 encoded-word(s) using the encoder
     * configured at 'Email.encodes.header', for {@see UnstructuredHeader} (e.g. Subject),
     * {@see MailboxHeader} (e.g. From) and {@see MailboxListHeader} (e.g. To, Cc, Bcc) display
     * names. Headers such as {@see DateHeader}, {@see IdentificationHeader},
     * {@see ParameterizedHeader} and {@see PathHeader} are left as-is.
     *
     * @return static a clone of this email, with encoded headers
     */
    public function build() : static
    {
        $this->prepareHeaders();
        $email = clone $this;

        $encode = static::config('encodes.header') == 'base64' ? 'B' : 'Q';
        foreach ($email->getHeaders()->all() as $header) {
            switch (true) {
                case $header instanceof DateHeader:
                case $header instanceof IdentificationHeader:
                case $header instanceof ParameterizedHeader:
                case $header instanceof PathHeader:
                    // Do not encode.
                    break;
                case $header instanceof UnstructuredHeader:
                    $origin = $header->getBody();
                    $header->setBody(static::needEncode($origin) ? static::encode($origin, $header, $encode) : $origin);
                    break;
                case $header instanceof MailboxHeader:
                    $origin = $header->getAddress();
                    $header->setAddress(static::needEncode($origin->getName()) ? new Address($origin->getAddress(), static::encode($origin->getName(), $header, $encode)) : $origin);
                    break;
                case $header instanceof MailboxListHeader:
                    $header->setAddresses(array_map(
                        fn (Address $origin) => static::needEncode($origin->getName()) ? new Address($origin->getAddress(), static::encode($origin->getName(), $header, $encode)) : $origin,
                        $header->getAddresses()
                    ));
                    break;
                default:
                    // Do not encode
            };
        }

        return $email;
    }

    /**
     * Check whether the given header display value needs RFC 2047 word-encoding, i.e. it does
     * not already conform to the RFC 2822 'phrase' grammar as-is.
     *
     * @param string $body value of the header to check
     * @return bool
     */
    protected static function needEncode(string $body) : bool
    {
        return !preg_match('/^'.AbstractHeader::PHRASE_PATTERN.'$/D', $body);
    }

    /**
     * Encode the given header display value into one or more RFC 2047 encoded-word(s).
     *
     * Delegates to PHP's mb_encode_mimeheader(), which splits multi-byte content on character
     * boundaries (never mid-character) when more than one encoded-word is needed, so every
     * resulting encoded-word remains independently decodable by a standard RFC 2047 decoder.
     * The encoded-word(s) are joined by a plain space (no "\r\n"), so the result can be safely
     * stored back as-is into a header's body/address name without being mistaken for content
     * that itself still needs word-encoding.
     *
     * @param string $body value to encode
     * @param HeaderInterface $header this value belongs to, used for its name/charset
     * @param 'B'|'Q' $encode used to word-encode $body ('B' or 'Q')
     * @return string encoded as "=?charset?encoding?...?=" (one or more, separated by a single space)
     */
    protected static function encode(string $body, HeaderInterface $header, string $encode) : string
    {
        $charset = $header->getCharset() ?? 'utf-8';

        $old = mb_internal_encoding();
        mb_internal_encoding($charset);
        try {
            return mb_encode_mimeheader($body, $charset, $encode, '', \strlen($header->getName().': '));
        } finally {
            mb_internal_encoding($old);
        }
    }

    /**
     * Send this email.
     *
     * @param string|null $mailer name that configured in 'Email.mailers'. (default: null to use 'Email.default_mailer')
     * @param Envelope|null $envelope
     * @return static
     * @throws ConfigNotDefineException
     */
    public function send(string|null $mailer = null, Envelope|null $envelope = null) : static
    {
        static::mailer($mailer)->send($this->build(), $envelope);
        return $this;
    }
}
