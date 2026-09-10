<?php

use Egulias\EmailValidator\Result\Reason\ConsecutiveDot;
use Egulias\EmailValidator\Result\Reason\DotAtEnd;
use Egulias\EmailValidator\Result\Reason\DotAtStart;
use Egulias\EmailValidator\Validation\RFCValidation;
use Rebet\Mail\Email;
use Rebet\Mail\Validator\EmailValidator;
use Rebet\Mail\Validator\Validation\LooseRFCValidation;
use Rebet\Tools\Utility\Env;
use Symfony\Component\Mailer\Transport;

/*
|##################################################################################################
| Mail Package Configurations
|##################################################################################################
| This file defines configuration for classes in Rebet\Mail package.
|
| The Rebet configuration file provides multiple ways to describe environment-dependent settings.
| You can use these methods when set environment-dependent settings.
|
| 1. Use `Env::promise('KEY')` to get value from `.env` file for each environment.
| 2. Use `App::when(['env' => value, ...])` to switch value by channel and environment.
| 3. Use `mail@{env}.php` file to override environment dependency value of `mail.php`
|
| You can also use `Config::refer()` to refer to the settings of other classes, use
| `Config::promise()` to get the settings by lazy evaluation, and have the values evaluated each
| time the settings are referenced.
|
| NOTE: If you want to get other default setting samples of configuration file, try check here.
|       https://github.com/rebet/rebet/tree/master/skeltons/app/core/configs
*/
return [
    /*
    |==============================================================================================
    | Email Configuration
    |==============================================================================================
    | This section defines mail sending settings.
    | You may change these defaults as required.
    */
    Email::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Default Mailer
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | This option controls the default mailer that gets used while sending mail. This mailer is
        | used when another is not explicitly specified when sending a given mail.
        */
        'default_mailer' => 'main',


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Mailers
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may define all of the mailers for your application as well as their transports.
        | You may even define multiple mailers for the same transport to group types of mail sent
        | by your application.
        |
        | The 'transport' 'dsn' option accepts any DSN supported by Symfony Mailer.
        | @see https://symfony.com/doc/current/mailer.html#using-built-in-transports
        |
        | If you want to use multiple mail sending environments (ex: multiple SMTP servers/services)
        | together for a single mailer, you can set 'transport' to Rebet\Mail\Transport\RoundRobinTransport
        | or Rebet\Mail\Transport\FailoverTransport instead of a single transport DSN.
        |  - @see Rebet\Mail\Transport\RoundRobinTransport : Distributes mail sending across the given
        |    transports in turn (load balancing), skipping any that are currently unavailable.
        |  - @see Rebet\Mail\Transport\FailoverTransport : Always uses the first available transport,
        |    falling over to the next one only when the current transport becomes unavailable.
        */
        'mailers' => [
            /*
            |------------------------------------------------------------------------------------
            | Main Mailer
            |------------------------------------------------------------------------------------
            | The mailer actually used to send mail for your application.
            */
            'main' => [
                'transport' => [
                    '@factory' => Transport::class."::fromDsn",
                    'dsn'      => Env::get('MAILER_DSN', 'null://null'),
                    // --- You can change only what you need for these default options for Transport::fromDsn() ---
                    // 'dispatcher' => null, // Instantiable class name of EventDispatcherInterface implementation [ex: Event::psrDispatcher()] or null to disable.
                    // 'client'     => null, // Instantiable class name of HttpClientInterface implementation, or null to disable.
                    // 'logger'     => null, // Instantiable class name of LoggerInterface implementation [ex: Log::channel()->driver()] or null to disable.
                ],
                // --- You can change only what you need for these default options for Symfony's Mailer ---
                // 'bus'        => null, // Instantiable class name of MessageBusInterface implementation, or null to disable.
                // 'dispatcher' => null, // Instantiable class name of EventDispatcherInterface implementation [ex: Event::psrDispatcher()] or null to disable.
            ],


            /*
            |------------------------------------------------------------------------------------
            | Unittest Mailer
            |------------------------------------------------------------------------------------
            | A mailer using an in-memory transport that does not actually send mail.
            | Useful for asserting sent mail contents in your tests.
            | Unless you need to run tests involving the dispatcher/bus/logger, there is
            | generally no need to make changes.
            */
            // 'unittest' => [
            //     'transport' => [
            //         '@factory'   => Rebet\Mail\Transport\InMemoryTransport::class,
            //         'dispatcher' => null,
            //         'logger'     => null,
            //     ],
            //     'bus'        => null,
            //     'dispatcher' => null,
            // ],
        ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Content and Mime Header Transfer Encodes
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Normally you don't need to change this setting, but if you want to change the
        | Content-Transfer-Encoding used for the mail header and body, you can set it here.
        |
        | Supported Options:
        |  - encodes.header : 'quoted-printable', 'base64'
        |  - encodes.body   : 'quoted-printable', 'base64', '8bit'
        */
        // 'encodes' => [
        //     'header' => 'base64',
        //     'body'   => 'base64',
        // ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | HTML to Text Generator
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Define how to auto-generate the plain text part of a mail from its HTML content when the
        | text part is not given explicitly.
        |
        | This is the default generator adopted when @see Email::generateTextBodyFromHtml() is called
        | without an explicit $generator argument.
        |
        | NOTE: If you want to use a different generator, you can set your own generator function here.
        */
        // 'html2text_generator' => fn (string $body) => (new Html2Text\Html2Text($body, ['width' => 0]))->getText(),
    ],


    /*
    |==============================================================================================
    | Email Validator Configuration
    |==============================================================================================
    | This section defines the validation rule used by Rebet's EmailValidator to validate email
    | addresses.
    |
    | Calling @see Rebet\Mail\Validator\EmailValidator::enable() replaces the validator used
    | internally by Symfony's Address class (Symfony\Component\Mailer\Address) with this
    | EmailValidator, so it takes effect for email address validation throughout Symfony Mailer.
    |
    | In a Rebet application, this is enabled automatically by the
    | @see Rebet\Application\Bootstrap\EmailValidatorEnable bootstrapper, which is included in the
    | default bootstrap list of WebKernel/CliKernel, so you don't usually need to call it yourself.
    */
    EmailValidator::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Email Validation Rule
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | You may set any instance of Egulias\EmailValidator\Validation\EmailValidation or
        | instantiation configuration for Rebet\Reflection\Reflector::instantiate().
        |
        | Supported Options:
        |  - @see Rebet\Mail\Validator\Validation\LooseRFCValidation
        |  - @see Rebet\Mail\Validator\Validation\MultipleValidation
        |  - and also you can use any egulias/email-validator validation.
        */
        'validation' => new RFCValidation(),
    ],


    /*
    |==============================================================================================
    | Loose RFC Validation Configuration
    |==============================================================================================
    | This section defines which invalid email results should be ignored (treated as valid) when
    | using LooseRFCValidation as the 'validation' of EmailValidator above.
    */
    LooseRFCValidation::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Ignore Invalid Email Reasons
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | List of Egulias\EmailValidator\Result\Reason\* class names that should not be treated as
        | invalid.
        |
        | Supported Options:
        |  - @see Egulias\EmailValidator\Result\Reason\DotAtEnd
        |  - @see Egulias\EmailValidator\Result\Reason\DotAtStart
        |  - @see Egulias\EmailValidator\Result\Reason\ConsecutiveDot
        |  - and also you can use any egulias/email-validator result reason.
        */
        'ignores' => [
            DotAtEnd::class,
            DotAtStart::class,
            ConsecutiveDot::class,
        ]
    ],
];
