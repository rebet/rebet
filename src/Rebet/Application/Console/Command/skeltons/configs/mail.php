<?php

use Egulias\EmailValidator\Result\Reason\ConsecutiveDot;
use Egulias\EmailValidator\Result\Reason\DotAtEnd;
use Egulias\EmailValidator\Result\Reason\DotAtStart;
use Egulias\EmailValidator\Validation\RFCValidation;
use Html2Text\Html2Text;
use Rebet\Mail\Email;
use Rebet\Mail\Transport\InMemoryTransport;
use Rebet\Mail\Validator\EmailValidator;
use Rebet\Mail\Validator\Validation\LooseRFCValidation;
use Rebet\Tools\Utility\Env;
use Symfony\Component\Mailer\Transport;

return [
    Email::class => [
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
    ],

    EmailValidator::class => [
        'validation' => new RFCValidation(),
    ],

    LooseRFCValidation::class => [
        'ignores' => [
            DotAtEnd::class,
            DotAtStart::class,
            ConsecutiveDot::class,
        ]
    ],
];
