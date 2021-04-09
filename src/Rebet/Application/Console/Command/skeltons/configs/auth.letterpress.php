<?php

use App\Model\User;
use Rebet\Auth\Auth;
use Rebet\Auth\AuthUser;
use Rebet\Auth\Guard\SessionGuard;
use Rebet\Auth\Guard\TokenGuard;
use Rebet\Auth\Password;
use Rebet\Auth\Provider\ArrayProvider;
use Rebet\Auth\Provider\DatabaseProvider;
use Rebet\Tools\Translation\Translator;

return [
    /*
    |==============================================================================================
    | Auth Configuration
    |==============================================================================================
    | This section defines authentication and authorization settings.
    | You may change these defaults as required, but they're a good start for many applications.
    |
    | See below for more detailed configuration examples for this file:
    | @see Rebet\Application\Console\Command\skeltons\configs\auth.letterpress.php
    */
    Auth::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Authentication Guards
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | You may define every authentication guard for your application.
        | Default configuration has been defined for you here which uses session storage and the
        | `user` authentication provider.
        |
        | Supported:
        |  - @see Rebet\Auth\Guard\SessionGuard
        |  - @see Rebet\Auth\Guard\TokenGuard
        |  - and also you can use any auth guard that extended Rebet\Auth\Guard\Guard.
        */
        'guards' => [
            //{%-- if $use_auth -%}
            'user:web' => [SessionGuard::class, 'provider' => 'user', 'fallback' => '/signin'],
            'user:api' => [TokenGuard::class  , 'provider' => 'user'],
            //{%-- else -%}
            // 'user:web' => [SessionGuard::class, 'provider' => 'user', 'fallback' => '/signin'],
            // 'user:api' => [TokenGuard::class  , 'provider' => 'user'],
            //{%-- endif -%}
        ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Authentication Providers
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | All authentication gaurds have an authentication provider.
        | This defines how the users are actually retrieved out of your database or other storage
        | mechanisms used by this application.
        |
        | If you have multiple user tables or models you may configure multiple sources which
        | represent each model / table. These sources may then be assigned to any extra
        | authentication guards you have defined.
        |
        | Supported:
        |  - @see Rebet\Auth\Provider\ArrayProvider
        |  - @see Rebet\Auth\Provider\DatabaseProvider
        |  - and also you can use any auth provider that extended Rebet\Auth\Provider\AuthProvider.
        */
        'providers' => [
            //{%-- if $use_auth -%}
            'user' => [
                //{%-- if $use_db -%}
                '@factory'     => DatabaseProvider::class,
                'entity'       => User::class,
                'precondition' => ['resign_at_null' => true],
                'alises'       => ['role' => '@user'],
                //{%-- else -%}
                '@factory'     => ArrayProvider::class,
                'users'        => [
                    ['user_id' => 1, 'active' => true, 'name' => '{! $auth_name !}', 'email' => '{! $auth_email !}', 'password' => '{! $auth_password !}'],
                    // If you want to add new user then write user information here.
                    // NOTE: You can use Rebet assistant `hash:password` command to create password hash.
                ],
                'precondition' => function ($user) { return $user['active'] ?? false; },
                'alises'       => ['role' => '@user'],
                //{%-- endif -%}
            ],
            //{%-- else -%}
            // 'user' => [
            //     '@factory'     => DatabaseProvider::class,
            //     'entity'       => 'App\\Model\\Entity\\User',
            //     'precondition' => ['resign_at_null' => true],
            //     'alises'       => ['role' => '@user']
            // ],
            //{%-- endif -%}
        ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | User Roles
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Define the role of the user.
        | You can use the roles defined here to control access to actions using routing and
        | controller annotations.
        |
        | You can also use `Auth::role()` and `Auth::user()->is()` methods, or `role` tag in views
        | to see if the target user belongs to a role.
        */
        'roles' => [
            //{%-- if $use_auth -%}
            'all'   => function (AuthUser $user) { return true; },
            'guest' => function (AuthUser $user) { return $user->isGuest(); },
            'user'  => function (AuthUser $user) { return $user->role === 'user'; },
            //{%-- else -%}
            // 'all'   => function (AuthUser $user) { return true; },
            // 'guest' => function (AuthUser $user) { return $user->isGuest(); },
            // 'user'  => function (AuthUser $user) { return $user->role === 'user'; },
            //{%-- endif -%}
        ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Model Control Policies
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Policies can define authorization logic for specific models and resources.
        |
        | You can use `Auth::policy()` and `Auth::user()->can()` methods, or 'can' tag in views to
        | see if the target user was allowed given action.
        */
        'policies' => [
            //{%-- if $use_auth && $use_db -%}
            User::class => [
                // '@before' => function (AuthUser $user, $target, string $action) { return $user->is('admin'); },
                'update' => function (AuthUser $user, User $target) { return $user->id === $target->user_id; },
            ],
            //{%-- else -%}
            // User::class => [
            //     // '@before' => function (AuthUser $user, $target, string $action) { return $user->is('admin'); },
            //     'update' => function (AuthUser $user, User $target) { return $user->id === $target->user_id; },
            // ],
            //{%-- endif -%}
        ],
    ],


    /*
    |==============================================================================================
    | Auth User
    |==============================================================================================
    | AuthUser is a wrapper class for treating different models (User and Admin, etc.) as
    | authenticated users in a unified manner.
    | This class achieves this by defining aliases for property.
    | Each alias definition is set by the 'alises' option in the 'providers' definition.
    */
    AuthUser::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Guest User Aliases
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Define an alias for the guest user.
        | If you have specific auth user properties defined on your system, define values for
        | guests here.
        */
        'guest_aliases' => [
            'name' => function ($user) { return Translator::get('message.guest_name') ?? 'Guest'; },
            'role' => '@guest',
        ],


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Aliases Max Recursion Depth
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Normally, it is not necessary to change the setting, but if necessary, change the setting.
        */
        // 'aliases_max_recursion_depth' => 20,
    ],


    /*
    |==============================================================================================
    | Password Configuration
    |==============================================================================================
    | Set the default algorithm and options used for password hashing.
    | Change the settings if necessary.
    */
    Password::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Password Algorithm
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Please specify the PHP constant of PASSWORD_*.
        */
        // 'algorithm' => PASSWORD_DEFAULT,


        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Password Algorithm Options
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | The contents of the options that can be specified differ for each algorithm, so refer to
        | the PHP Doc of the specified algorithm for the contents of the options.
        */
        // 'options' => [],
    ],
];
