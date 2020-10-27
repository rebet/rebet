<?php

use Egulias\EmailValidator\Exception\ConsecutiveDot;
use Egulias\EmailValidator\Exception\DotAtEnd;
use Egulias\EmailValidator\Exception\DotAtStart;
use Html2Text\Html2Text;
use Rebet\Mail\Mail;
use Rebet\Mail\Transport\ArrayTransport;
use Rebet\Mail\Transport\LogTransport;
use Rebet\Mail\Transport\SendmailTransport;
use Rebet\Mail\Transport\SmtpTransport;
use Rebet\Mail\Validator\Validation\LooseRFCValidation;

return [
    Mail::class => [
        'development' => false,
        'unittest'    => false,
        'initialize'  => [
            'handler' => null, // function (Swift_DependencyContainer $c) { ... }
            'default' => [
                'charset'          => 'utf-8',
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
                'transporter' => SmtpTransport::class,
                'host'        => 'localhost',
                'port'        => 25,
                'username'    => null,
                'password'    => null,
                'encryption'  => null,
                'options'     => [],
                'plugins'     => [],
            ],
            'sendmail' => [
                'transporter' => SendmailTransport::class,
                'command'     => '/usr/sbin/sendmail -bs',
                'options'     => [],
                'plugins'     => [],
            ],
            'log' => [
                'transporter' => LogTransport::class,
                'logger'      => null,
                'options'     => [],
                'plugins'     => [],
            ],
            'test' => [
                'transporter' => ArrayTransport::class,
                'options'     => [],
                'plugins'     => [],
            ],
        ],
        'default_transport'     => 'smtp',
        'development_transport' => 'log',
        'unittest_transport'    => 'test',
        'alternative_generator' => [
            'text/html' => [
                'text/plain' => function (string $body, array $options = []) {
                    return (new Html2Text($body, array_merge(['width' => 0], $options)))->getText();
                },
            ],
        ],
    ],

    LooseRFCValidation::class => [
        'ignores' => [
            DotAtEnd::class,
            DotAtStart::class,
            ConsecutiveDot::class,
        ]
    ],
];
