<?php

use League\Flysystem\Local\LocalFilesystemAdapter;
use Rebet\Application\App;
use Rebet\Filesystem\BuiltinFilesystem;
use Rebet\Filesystem\Storage;

/*
|##################################################################################################
| Filesystem Package Configurations
|##################################################################################################
| This file defines configuration for classes in Rebet\Filesystem package.
|
| The Rebet configuration file provides multiple ways to describe environment-dependent settings.
| You can use these methods when set environment-dependent settings.
|
| 1. Use `Env::promise('KEY')` to get value from `.env` file for each environment.
| 2. Use `App::when(['env' => value, ...])` to switch value by channel and environment.
| 3. Use `filesystem@{env}.php` file to override environment dependency value of `filesystem.php`
|
| You can also use `Config::refer()` to refer to the settings of other classes, use
| `Config::promise()` to get the settings by lazy evaluation, and have the values evaluated each
| time the settings are referenced.
|
| NOTE: If you want to get other default setting samples of configuration file, try check here.
|       https://github.com/rebet/rebet/tree/master/src/Rebet/Application/Console/Command/skeltons/configs
*/
return [
    /*
    |==============================================================================================
    | Builtin Filesystem Configuration
    |==============================================================================================
    | Rebet's file system uses `league/flysystem` as a driver and provides a built-in filesystem
    | class to operate it.
    */
    BuiltinFilesystem::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Flysystem Driver
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Normally you don't need to change this setting, but you can optionally configure a custom
        | driver that implements League\Flysystem\FilesystemOperator.
        */
        // 'driver' => League\Flysystem\Filesystem::class,
    ],


    /*
    |==============================================================================================
    | Storage Configuration
    |==============================================================================================
    | This section defines filesystem storage settings.
    | You may change these defaults as required.
    */
    Storage::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Filesystem
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Normally you don't need to change this setting, but you can optionally configure a custom
        | filesystem that implements Rebet\Filesystem\Filesystem.
        */
        // 'filesystem' => BuiltinFilesystem::class,

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Private Disk
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Specifies the disk name to use as a private disk.
        */
        'private_disk' => 'private',

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Public Disk
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Specifies the disk name to use as a public disk.
        */
        'public_disk' => 'public',

        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Disks
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you may configure as many filesystem "disks" as you wish, and you may even configure
        | multiple disks of the same driver.
        | You can use any of filesystem adapter implemented League\Flysystem\FilesystemAdapter.
        |
        | Provided Adapters:
        |  - Default Supported
        |    - League\Flysystem\Local\LocalFilesystemAdapter::class
        |    - League\Flysystem\InMemory\InMemoryFilesystemAdapter::class : for tests
        |  - For AWS S3 V3 : `composer require league/flysystem-aws-s3-v3:^3.0`
        |    - League\Flysystem\AwsS3V3\AwsS3V3Adapter::class
        |  - For Memory : `composer require league/flysystem-memory:^3.0`
        |    - League\Flysystem\InMemory\InMemoryFilesystemAdapter::class
        |  - For SFTP : `composer require league/flysystem-sftp-v3:^3.0`
        |    - League\Flysystem\PhpseclibV3\SftpAdapter::class
        |  - And you can find other Officially/Community Supported Adapters in
        |    https://github.com/thephpleague/flysystem/tree/3.x
        */
        'disks' => [
            /*
            |--------------------------------------------------------------------------------------
            | Private Disk Settings
            |--------------------------------------------------------------------------------------
            | A filesystem disk for private using Local file storage.
            */
            'private' => [
                'adapter' => [
                    '@factory' => LocalFilesystemAdapter::class,
                    'location' => App::structure()->privateStorage(),
                    // --- You can change only what you need for these default options ---
                    // 'writeFlags'   => LOCK_EX,
                    // 'linkHandling' => LocalFilesystemAdapter::DISALLOW_LINKS,
                ],
                'config' => null,
            ],

            /*
            |--------------------------------------------------------------------------------------
            | Public Disk Settings
            |--------------------------------------------------------------------------------------
            | A filesystem disk for public using Local file storage.
            */
            'public' => [
                'adapter' => [
                    '@factory' => LocalFilesystemAdapter::class,
                    'location' => App::structure()->publicStorage(),
                    // --- You can change only what you need for these default options ---
                    // 'writeFlags'   => LOCK_EX,
                    // 'linkHandling' => LocalFilesystemAdapter::DISALLOW_LINKS,
                ],
                'config' => [
                    'visibility' => 'public',
                    'url'        => App::structure()->storageUrl(),
                ],
            ],
        ],
    ],
];
