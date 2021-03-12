<?php

use App\Model\User;
use App\Stub\Address;
use Rebet\Auth\Auth;
use Rebet\Auth\AuthUser;
use Rebet\Auth\Guard\SessionGuard;
use Rebet\Auth\Guard\TokenGuard;
use Rebet\Auth\Provider\ArrayProvider;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Utility\Securities;

$users = [
    ['user_id' => 1, 'role' => 'admin', 'name' => 'Admin'        , 'signin_id' => 'admin'        , 'email' => 'admin@rebet.local'        , 'password' => '$2y$04$68GZ8.IwFPFiVsae03fP7uMD76RYsEp9WunbITtrdRgvtJO1DGrim', 'api_token' => Securities::hash('token_1'), 'resigned_at' => null], // password: admin
    ['user_id' => 2, 'role' => 'user' , 'name' => 'User'         , 'signin_id' => 'user'         , 'email' => 'user@rebet.local'         , 'password' => '$2y$04$o9wMO8hXHHFpoNdLYRBtruWIUjPMU3Jqw9JAS0Oc7LOXiHFfn.7F2', 'api_token' => Securities::hash('token_2'), 'resigned_at' => null], // password: user
    ['user_id' => 3, 'role' => 'user' , 'name' => 'Resignd User' , 'signin_id' => 'user.resignd' , 'email' => 'user.resignd@rebet.local' , 'password' => '$2y$04$GwwjNndAojOi8uFu6xwFHe6L6Q/v6/7VynBatMHhCyfNt7momtiqK', 'api_token' => Securities::hash('token_3'), 'resigned_at' => DateTime::createDateTime('2001-01-01 12:34:56')], // password: user.resignd
    ['user_id' => 4, 'role' => 'user' , 'name' => 'Editable User', 'signin_id' => 'user.editable', 'email' => 'user.editable@rebet.local', 'password' => '$2y$10$3OTm0Ps5BeaYy5YZ619.4.gXwENPc4fVJBnMvBM5/5m/s0H6Nwg0O', 'api_token' => Securities::hash('token_4'), 'resigned_at' => null], // password: user.editable
];

return [
    Auth::class => [
        'guards' => [
            'admin' => [SessionGuard::class, 'provider' => 'admin', 'fallback' => '/admin/signin'],
            'web'   => [SessionGuard::class, 'provider' => 'user' , 'fallback' => '/user/signin'],
            'api'   => [TokenGuard::class  , 'provider' => 'user'],
        ],
        'providers' => [
            'user' => [
                '@factory'     => ArrayProvider::class,
                'users'        => $users,
                'precondition' => function ($user) { return !isset($user['resigned_at']); }
            ],
            'admin' => [
                '@factory'     => ArrayProvider::class,
                'users'        => $users,
                'precondition' => function ($user) { return !isset($user['resigned_at']) && $user['role'] === 'admin'; }
            ],
        ],
        'roles' => [
            'admin'    => function (AuthUser $user) { return $user->role === 'admin'; },
            'user'     => function (AuthUser $user) { return $user->role === 'user'; },
            'editable' => function (AuthUser $user) { return $user->id === 4; },
        ],
        'policies' => [
            User::class => [
                '@before' => function (AuthUser $user, $target, string $action) { return $user->is('admin'); },
                'update'  => function (AuthUser $user, User $target) { return $user->id === $target->user_id; },
                'create'  => function (AuthUser $user) { return $user->is('editable'); },
            ],
            Address::class => [
                'create'  => function (AuthUser $user, string $target, array $addresses) {
                    return !$user->isGuest() && count($addresses) < 5 ;
                },
            ]
        ]
    ],


    AuthUser::class => [
        'guest_aliases' => [
            'role' => '@guest',
        ],
    ],
];
