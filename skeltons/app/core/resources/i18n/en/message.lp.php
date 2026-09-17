<?php
declare(strict_types=1);

return [
    '@errors' => [
        'outer' => '<article class="message is-small is-danger has-text-left" style="margin-top: 8px;"><ul class="message-body" style="padding: 5px;">:messages</ul></article>',
        'inner' => '<li><span class="icon"><i class="fas fa-exclamation-triangle"></i></span> :message</li>',
        'class' => 'is-danger',
        'icon'  => '<span class="icon is-small is-right"><i class="fas fa-exclamation-triangler"></i></span>',
    ],

    'http' => [
        403 => [
            'title'  => null,
            'detail' => 'You do not have permission to access the requested page.',
        ],
        404 => [
            'title'  => null,
            'detail' => 'The requested URL does not exist, or it has already expired.',
        ],
        500 => [
            'title'  => null,
            'detail' => "An unexpected error has occurred.\nWe apologize for the inconvenience. Please try again later.",
        ],
    ],

    'signin_failed' => 'Sign-in failed. Please check your email address and password, then try again.',
];
