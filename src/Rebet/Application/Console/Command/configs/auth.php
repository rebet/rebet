<?php

use Rebet\Auth\Auth;
use Rebet\Auth\AuthUser;
use Rebet\Auth\Password;

return [
    Auth::class => [
        'guards'    => [],
        'providers' => [],
        'roles'     => [
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
];
