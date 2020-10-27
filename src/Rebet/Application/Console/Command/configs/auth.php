<?php

use Rebet\Auth\Auth;
use Rebet\Auth\AuthUser;
use Rebet\Auth\Guard\SessionGuard;
use Rebet\Auth\Guard\TokenGuard;
use Rebet\Auth\Password;
use Rebet\Auth\Provider\ArrayProvider;

return [
    Auth::class => [
        'authenticator' => [
            'web' => [
                'guard'    => null,
                'provider' => null,
                'fallback' => null, // url or function(Request):Response
            ],
            'api' => [
                'guard'    => null,
                'provider' => null,
                'fallback' => null, // url or function(Request):Response
            ],
        ],
        'roles' => [
            'all'   => function (AuthUser $user) { return true; },
            'guest' => function (AuthUser $user) { return $user->isGuest(); },
        ],
        'policies' => [],
    ],

    AuthUser::class => [
        'guest_aliases'               => [],
        'aliases_max_recursion_depth' => 20,
    ],

    Password::class => [
        'algorithm' => PASSWORD_DEFAULT,
        'options'   => [],
    ],

    SessionGuard::class => [
        'remember_days' => 0,
    ],

    TokenGuard::class => [
        'input_key'   => 'api_token',
        'storage_key' => 'api_token',
    ],

    ArrayProvider::class => [
        'signin_id_name' => 'email',
        'precondition'   => function ($user) { return true; },
    ],
];
