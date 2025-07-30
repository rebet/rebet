<?php
namespace Rebet\Mail;

use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use Egulias\EmailValidator\Validation\Extra\SpoofCheckValidation;
use Html2Text\Html2Text;
use Rebet\Mail\Encoder\Base64Encoder;
use Rebet\Mail\Mime\HeaderEncoder\Base64HeaderEncoder;
use Rebet\Mail\Mime\HeaderSet;
use Rebet\Mail\Mime\MimeMessage;
use Rebet\Mail\Mime\MimePart;
use Rebet\Mail\Transport\ArrayTransport;
use Rebet\Mail\Transport\LogTransport;
use Rebet\Mail\Transport\SendmailTransport;
use Rebet\Mail\Transport\SmtpTransport;
use Rebet\Mail\Validator\EmailValidator;
use Rebet\Mail\Validator\Validation\LooseRFCValidation;
use Rebet\Mail\Validator\Validation\MultipleValidation;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Template\Renderable;
use Swift_Attachment;
use Swift_DependencyContainer;
use Swift_EmbeddedFile;
use Swift_Encoder_Base64Encoder;
use Swift_Mime_ContentEncoder;
use Swift_Mime_MimePart;
use Swift_Mime_SimpleHeaderFactory;
use Swift_Mime_SimpleMessage;
use Swift_Mime_SimpleMimeEntity;

/**
 * Mail Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Mail
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'development'           => false,
            'unittest'              => false,
            'default_transport'     => 'smtp',
            'development_transport' => 'log',
            'unittest_transport'    => 'test',
            'initialize'            => [
                'handler' => null, // function (Swift_DependencyContainer $c) { ... }
                'default' => [
                    'charset'          => 'UTF-8',
                    'idright'          => null,
                    'content_encoder'  => 'mime.base64contentencoder',
                    'header_encoder'   => 'mime.base64headerencoder',
                    'param_encoder'    => 'mime.base64encoder',
                    'address_encoder'  => 'address.utf8addressencoder',
                    'email_validation' => [],
                ],
            ],
            'transports' => [
                'smtp' => [
                    'transporter' => [
                        '@factory' => SmtpTransport::class,
                    ],
                    'plugins'     => [],
                ],
                'sendmail' => [
                    'transporter' => [
                        '@factory' => SendmailTransport::class,
                    ],
                    'plugins'     => [],
                ],
                'log' => [
                    'transporter' => [
                        '@factory' => LogTransport::class,
                    ],
                    'plugins'     => [],
                ],
                'test' => [
                    'transporter' => [
                        '@factory' => ArrayTransport::class,
                    ],
                    'plugins'     => [],
                ],
            ],
            'alternative_generator' => [
                'text/html' => [
                    'text/plain' => function (string $body, array $options = []) {
                        return (new Html2Text($body, array_merge(['width' => 0], $options)))->getText();
                    },
                ],
            ],
        ];
    }

    /**
     * @var bool initialized Swift settings or not
     */
    protected static $initialized = false;

    /**
     * Mailers
     *
     * @var Mailer[]
     */
    protected static $mailers = [];

    /**
     * @var MimeMessage
     */
    protected $message;

    /**
     * @var array of embedded file CIDs.
     */
    protected $embedded_cids = [];

    /**
     * @var HeaderSet
     */
    protected $headers;

    /**
     * Clear the Mail context
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$initialized = false;
        static::$mailers     = [];
    }

    /**
     * Get/Set development mode or not.
     *
     * @param bool|null $is_development (default: null for get development mode or not)
     * @return bool
     */
    public static function development(?bool $is_development = null) : bool
    {
        if ($is_development === null) {
            return static::config('development');
        }
        static::setConfig(['development' => $is_development]);
        return $is_development;
    }

    /**
     * Get/Set unittest mode or not.
     *
     * @param bool|null $is_unittest (default: null for get unittest mode or not)
     * @return bool
     */
    public static function unittest(?bool $is_unittest = null) : bool
    {
        if ($is_unittest === null) {
            return static::config('unittest');
        }
        static::setConfig(['unittest' => $is_unittest]);
        return $is_unittest;
    }

    /**
     * Select the transport according to the configuration.
     *
     * @param string|null $transport name that configured in 'Mail.transports'. (default: null for depend on configuration 'default_transport')
     * @return string
     */
    protected static function adoptTransport(?string $transport = null) : string
    {
        switch (true) {
            case static::unittest():    return static::config('unittest_transport');
            case static::development(): return static::config('development_transport');
        }
        return $transport ?? static::config('default_transport');
    }

    /**
     * Get the mailer for given transport.
     *
     * @param string|null $transport name that configured in 'Mail.transports'. (default: null for depend on configuration 'default_transport')
     * @return Mailer
     */
    public static function mailer(?string $transport = null) : Mailer
    {
        static::init();

        $transport = static::adoptTransport($transport);
        if (isset(static::$mailers[$transport])) {
            return static::$mailers[$transport];
        }

        $mailer = new Mailer(static::configInstantiate("transports.{$transport}.transporter"));
        foreach (static::config("transports.{$transport}.plugins", false, []) as $config) {
            $mailer->plugin(Reflector::instantiate($config));
        }
        return static::$mailers[$transport] = $mailer;
    }

    /**
     * Get the Swift DI Container.
     *
     * @return Swift_DependencyContainer
     */
    public static function container() : Swift_DependencyContainer
    {
        static::init();
        return Swift_DependencyContainer::getInstance();
    }

    /**
     * Initilize Swift DI Container Settings.
     *
     * @return void
     */
    protected static function init() : void
    {
        if (static::$initialized) {
            return;
        }
        Swift_DependencyContainer::getInstance()
            ->register('properties.charset')
            ->asValue(static::config('initialize.default.charset'))

            ->register('mime.idgenerator.idright')
            // As SERVER_NAME can come from the user in certain configurations, check that
            // it does not contain forbidden characters (see RFC 952 and RFC 2181). Use
            // preg_replace() instead of preg_match() to prevent DoS attacks with long host names.
            ->asValue(
                static::config(
                    'initialize.default.idright',
                    false,
                    !empty($_SERVER['SERVER_NAME']) && '' === preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'rebet.generated'
                )
            )

            ->register('mime.idgenerator')
            ->asSharedInstanceOf('Swift_Mime_IdGenerator')
            ->withDependencies([
                'mime.idgenerator.idright',
            ])

            ->register('mime.base64headerencoder')
            ->asSharedInstanceOf(Base64HeaderEncoder::class)

            ->register('mime.base64encoder')
            ->asSharedInstanceOf(Swift_Encoder_Base64Encoder::class)

            ->register('email.validation.rfc.strict')
            ->asNewInstanceOf(NoRFCWarningsValidation::class)

            ->register('email.validation.rfc')
            ->asNewInstanceOf(RFCValidation::class)

            ->register('email.validation.rfc.loose')
            ->asNewInstanceOf(LooseRFCValidation::class)

            ->register('email.validation.spoof')
            ->asNewInstanceOf(SpoofCheckValidation::class)

            ->register('email.validation.dns')
            ->asNewInstanceOf(DNSCheckValidation::class)

            ->register('email.validation')
            ->asNewInstanceOf(MultipleValidation::class)
            ->withDependencies(static::config('initialize.default.email_validation', false) ?: ['email.validation.rfc'])

            ->register('email.validator')
            ->asSharedInstanceOf(EmailValidator::class)

            ->register('mime.headerfactory')
            ->asSharedInstanceOf(Swift_Mime_SimpleHeaderFactory::class)
            ->withDependencies([
                static::config('initialize.default.header_encoder'),
                static::config('initialize.default.param_encoder'),
                'email.validator',
                'properties.charset',
                static::config('initialize.default.address_encoder')
            ])

            ->register('mime.message')
            ->asNewInstanceOf(Swift_Mime_SimpleMessage::class)
            ->withDependencies([
                'mime.headerset',
                static::config('initialize.default.content_encoder'),
                'cache',
                'mime.idgenerator',
                'properties.charset',
            ])

            ->register('mime.part')
            ->asNewInstanceOf(Swift_Mime_MimePart::class)
            ->withDependencies([
                'mime.headerset',
                static::config('initialize.default.content_encoder'),
                'cache',
                'mime.idgenerator',
                'properties.charset',
            ])

            ->register('message.message')
            ->asNewInstanceOf(MimeMessage::class)

            ->register('message.mimepart')
            ->asNewInstanceOf(MimePart::class)
        ;

        static::$initialized = true;

        if ($handler = static::config('initialize.handler', false)) {
            $handler(Swift_DependencyContainer::getInstance());
        }
    }

    /**
     * Register alternative generator.
     *
     * @param string $from_content_type that the Body Content-Type
     * @param string $to_content_type that an Alternative Part Content-Type you want to generate.
     * @param \Closure $generator function(string $body, array $options = []):string { ... }
     * @return void
     */
    public static function generator(string $from_content_type, string $to_content_type, \Closure $generator) : void
    {
        static::setConfig(['alternative_generator' => [$from_content_type => [$to_content_type => $generator]]]);
    }

    /**
     * Create a new TEXT mail message.
     *
     * @return Mail
     */
    public static function text() : Mail
    {
        return new Mail();
    }

    /**
     * Create a new HTML mail message.
     *
     * @return Mail
     */
    public static function html() : Mail
    {
        return new Mail('text/html');
    }

    /**
     * Create a new message of given $content_type.
     *
     * @param string $content_type (default: 'text/plain')
     */
    public function __construct(string $content_type = 'text/plain')
    {
        static::init();
        $this->message = new MimeMessage();
        $this->headers = new HeaderSet($this);
        $this->contentType($content_type);
    }

    /**
     * Resolve address(es) mixtured of multiple formats to ['foo@bar.com' => 'Name', ...].
     *
     * @param string|array $addresses can be 'foo@bar.com', 'Foo <foo@bar.com>', ['foo@bar.com' => 'Foo'] or ['foo@bar.com' => 'Foo', 'baz@bar.com', 'Qux <qux@bar.com>', ...]
     * @return array [address => name, ...]
     */
    public static function resolve($addresses) : array
    {
        $resolves  = [];
        foreach ((array)$addresses as $address => $name) {
            if (is_string($address)) {
                $resolves[$address] = $name;
                continue;
            }
            $address = $name;
            if (preg_match('/^(("(?<quotatted_name>[^"].*)" *)|((?<name>[^<].*) *))?<(?<address>.*)>$/', trim($address), $matches)) {
                $resolves[$matches['address']] = $matches['quotatted_name'] ?: trim($matches['name']) ?: null;
                continue;
            }
            $resolves[$address] = null;
        }
        return $resolves;
    }

    /**
     * Get/Set "From" addresses from/to the message.
     *
     * @param string|array|null $addresses can contains an address, a name combined address like 'Name <foo@bar.com>' or a map of address and name like [address => name, ...].
     * @return null|array|self
     */
    public function from($addresses = null)
    {
        if ($addresses === null) {
            return $this->message->getFrom() ?? [];
        }
        $this->message->setFrom(static::resolve((array)$addresses));
        return $this;
    }

    /**
     * Get/Set the "Sender" address from/to the message.
     *
     * @param null|string|array $address is an address, a name combined address like 'Name <foo@bar.com>' or a singlemap of address and name like [address => name]. (default: null for get sender)
     * @return null|array|self
     */
    public function sender($address = null)
    {
        if ($address === null) {
            return $this->message->getSender() ?? [];
        }
        $this->message->setSender(static::resolve($address));
        return $this;
    }

    /**
     * Get/Set the "Return-Path" address from/to the message.
     *
     * @param null|string|array $address is an address, a name combined address like 'Name <foo@bar.com>' or a single map of address and name like [address => name]. (Ignore the name and only the address part is used) (default: null for get return-path)
     * @return null|string|self
     */
    public function returnPath($address = null)
    {
        if ($address === null) {
            return $this->message->getReturnPath() ?? [];
        }
        $this->message->setReturnPath(array_key_first(static::resolve($address)));
        return $this;
    }

    /**
     * Get/Set recipients(=To) from/to the message.
     *
     * @param string|array|null $addresses can contains an address, a name combined address like 'Name <foo@bar.com>' or a map of address and name like [address => name, ...].(default: null for get to recipients)
     * @return null|array|self
     */
    public function to($addresses = null)
    {
        if ($addresses === null) {
            return $this->message->getTo() ?? [];
        }
        $this->message->setTo(static::resolve($addresses));
        return $this;
    }

    /**
     * Get/Set carbon copies(=Cc) from/to the message.
     *
     * @param string|array|null $addresses can contains an address, a name combined address like 'Name <foo@bar.com>' or a map of address and name like [address => name, ...]. (default: null for get cc recipients)
     * @return null|array|self
     */
    public function cc($addresses = null)
    {
        if ($addresses === null) {
            return $this->message->getCc() ?? [];
        }
        $this->message->setCc(static::resolve($addresses));
        return $this;
    }

    /**
     * Get/Set blind carbon copies(=Bcc) from/to the message.
     *
     * @param string|array|null $addresses can contains an address, a name combined address like 'Name <foo@bar.com>' or a map of address and name like [address => name, ...]. (default: null for get bcc recipients)
     * @return null|array|self
     */
    public function bcc($addresses = null)
    {
        if ($addresses === null) {
            return $this->message->getBcc() ?? [];
        }
        $this->message->setBcc(static::resolve($addresses));
        return $this;
    }

    /**
     * Get/Set "Reply-To" addresses from/to the message.
     *
     * @param string|array|null $addresses can contains an address, a name combined address like 'Name <foo@bar.com>' or a map of address and name like [address => name, ...]. (default: null for get Reply-To recipients)
     * @return null|array|self
     */
    public function replyTo($addresses = null)
    {
        if ($addresses === null) {
            return $this->message->getReplyTo() ?? [];
        }
        $this->message->setReplyTo(static::resolve($addresses));
        return $this;
    }

    /**
     * Get/Set "Disposition-Notification-To" addresses from/to the message.
     *
     * @param string|array|null $addresses can contains an address, a name combined address like 'Name <foo@bar.com>' or a map of address and name like [address => name, ...].(default: null for get Disposition-Notification-To recipients)
     * @return null|array|self
     */
    public function dispositionNotificationTo($addresses = null)
    {
        if ($addresses === null) {
            return $this->message->getReadReceiptTo() ?? [];
        }
        $this->message->setReadReceiptTo(static::resolve($addresses));
        return $this;
    }

    /**
     * Get/Set the subject of the message.
     *
     * @param string|null $subject (default: null for get subject)
     * @return null|string|self
     */
    public function subject(?string $subject = null)
    {
        if ($subject === null) {
            return $this->message->getSubject();
        }
        $this->message->setSubject($subject);
        return $this;
    }

    /**
     * Get/Set the Content-Type of this entity.
     *
     * @param string|null $content_type
     * @return string|self
     */
    public function contentType(?string $content_type = null)
    {
        if ($content_type === null) {
            return $this->message->getContentType();
        }
        $this->message->setContentType($content_type);
        return $this;
    }

    /**
     * Get/Set the Content-Type charset of this entity.
     *
     * @param string|null $charset
     * @return string|self
     */
    public function charset(?string $charset = null)
    {
        if ($charset === null) {
            return $this->message->getCharset();
        }
        $this->message->setCharset($charset);
        return $this;
    }

    /**
     * Get/Set the Content-Type format of this entity.
     *
     * @param string|null $format 'flowed' or 'fixed' (default: null for get format)
     * @return null|string|self
     */
    public function format(?string $format = null)
    {
        if ($format === null) {
            return $this->message->getFormat();
        }
        $this->message->setFormat($format);
        return $this;
    }

    /**
     * Get/Set the Content-Type delsp of this entity.
     *
     * @param bool|null $delsp (default: null for get delsp)
     * @return bool|self
     */
    public function delsp(?bool $delsp = null)
    {
        if ($delsp === null) {
            return $this->message->getDelSp();
        }
        $this->message->setDelSp($delsp);
        return $this;
    }

    /**
     * Get/Set Content Encoder (Content-Transfer-Encoding) from/to this message.
     *
     * @param string|Swift_Mime_ContentEncoder|null $encoder instanse or name like 'quoted-printable', '7bit', '8bit', 'base64' or Swift DI name of "mime.{$encoder}contentencoder". (default: null for get encoder)
     * @return Swift_Mime_ContentEncoder|self
     */
    public function encoder($encoder = null)
    {
        if ($encoder === null) {
            return $this->message->getEncoder();
        }

        if ($encoder instanceof Swift_Mime_ContentEncoder) {
            $this->message->setEncoder($encoder);
            return $this;
        }

        switch ($encoder) {
            case 'quoted-printable':
                $this->message->setEncoder(static::container()->lookup('mime.qpcontentencoder'));
            break;
            default:
                $this->message->setEncoder(static::container()->lookup("mime.{$encoder}contentencoder"));
        }
        return $this;
    }

    /**
     * Get/Set the message priority level that 1 is the highest priority and 5 is the lowest.
     *
     * @param int $level
     * @return self|int
     */
    public function priority(?int $level = null)
    {
        if ($level === null) {
            return intval($this->message->getPriority());
        }
        $this->message->setPriority($level);
        return $this;
    }

    /**
     * Attach a file to the message.
     *
     * @param string $file path
     * @param string|null $filename (default: null)
     * @param string|null $content_type (default: null)
     * @return self
     */
    public function attach(string $file, ?string $filename = null, ?string $content_type = null) : self
    {
        $attachment = Swift_Attachment::fromPath($file, $content_type);
        $this->message->attach($filename ? $attachment->setFilename($filename) : $attachment);
        return $this;
    }

    /**
     * Attach in-memory data as an attachment.
     *
     * @param string $data
     * @param string|null $filename (default: null)
     * @param string|null $content_type (default: null)
     * @return self
     */
    public function attachData(string $data, ?string $filename = null, ?string $content_type = null) : self
    {
        $attachment = new Swift_Attachment($data, $filename, $content_type);
        $this->message->attach($attachment);
        return $this;
    }

    /**
     * Embed a file in the message and get the CID.
     *
     * @param string $file
     * @param string|null $filename (default: null for basename of $file)
     * @return string CID
     */
    public function embed(string $file, ?string $filename = null) : string
    {
        return $this->embedded_cids[$filename ?? basename($file)] = $this->message->embed($filename ? Swift_EmbeddedFile::fromPath($file)->setFilename($filename) : Swift_EmbeddedFile::fromPath($file));
    }

    /**
     * Embed in-memory data in the message and get the CID.
     *
     * @param string $data
     * @param string $filename
     * @param string|null $content_type (default: null)
     * @return string CID
     */
    public function embedData(string $data, string $filename, ?string $content_type = null)
    {
        return $this->embedded_cids[$filename] = $this->message->embed(new Swift_EmbeddedFile($data, $filename, $content_type));
    }

    /**
     * Get a embedded CID of given file name.
     *
     * @param string $filename
     * @return string|null
     */
    public function cid(string $filename) : ?string
    {
        return $this->embedded_cids[$filename] ?? null ;
    }

    /**
     * Get/Set the Date at which this message was created.
     *
     * @param \DateTimeInterface $date_time (default: null for get date)
     * @return DateTime|self
     */
    public function date(?\DateTimeInterface $date_time = null)
    {
        if ($date_time === null) {
            return DateTime::valueOf($this->message->getDate());
        }
        $this->message->setDate($date_time);
        return $this;
    }

    /**
     * Get/Set the body of this entity.
     *
     * @param null|string|Renderable $body (default: null for get body)
     * @param string|null $content_type (default: null)
     * @param string|null $charset (default: null)
     * @return string|self
     */
    public function body($body = null, ?string $content_type = null, ?string $charset = null)
    {
        if ($body === null) {
            return $this->message->getBody();
        }
        $this->message->setBody($body, $content_type, $charset);
        return $this;
    }

    /**
     * Generate alternative part from body text.
     *
     * @param string|null $content_type (default: 'text/plain')
     * @param string|null $charset (default: null)
     * @param array $charset (options: [])
     * @return self
     */
    public function generateAlternativePart(?string $content_type = 'text/plain', ?string $charset = null, array $options = []) : self
    {
        return $this->part(
            Reflector::evaluate(static::config("alternative_generator.{$this->contentType()}.{$content_type}"), [$this->body(), $options]),
            $content_type,
            $charset
        );
    }

    /**
     * Add a MimePart to this Message.
     *
     * @param int|string|Renderable $body (default: 0 for get part)
     * @param string|null $content_type
     * @param string|null $charset
     * @return null|Swift_Mime_SimpleMimeEntity|self
     */
    public function part($body = 0, ?string $content_type = null, ?string $charset = null)
    {
        if (is_int($body)) {
            return $this->message->getPart($body);
        }
        $this->message->addPart($body, $content_type, $charset);
        return $this;
    }

    /**
     * Get/Set Message-ID from/to this message
     *
     * @param string|null $id (default: null for get ID)
     * @return string|self
     */
    public function id(?string $id = null)
    {
        if ($id === null) {
            return $this->message->getId();
        }
        $this->message->setId($id);
        return $this;
    }

    /**
     * Get headers and start header setting method chain.
     *
     * @return HeaderSet
     */
    public function headers() : HeaderSet
    {
        return $this->headers;
    }

    /**
     * Get this message as a complete string.
     *
     * @return string
     */
    public function toString() : string
    {
        return $this->message->toString();
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
        return $this->message->toReadableString();
    }

    /**
     * Convert to the Message instance.
     *
     * @return MimeMessage
     */
    public function message() : MimeMessage
    {
        return $this->message;
    }

    /**
     * Send the mail using given transport.
     *
     * @param string|null $transport name (default: null for use default transport)
     * @return array of failed recipients
     */
    public function send(?string $transport = null) : array
    {
        return static::mailer($transport)->send($this);
    }
}
