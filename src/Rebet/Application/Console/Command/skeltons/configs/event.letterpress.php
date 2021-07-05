<?php

use Rebet\Event\Event;

/*
|##################################################################################################
| Event Package Configurations
|##################################################################################################
| This file defines configuration for classes in Rebet\Event package.
|
| The Rebet configuration file provides multiple ways to describe environment-dependent settings.
| You can use these methods when set environment-dependent settings.
|
| 1. Use `Env::promise('KEY')` to get value from `.env` file for each environment.
| 2. Use `App::when(['env' => value, ...])` to switch value by channel and environment.
| 3. Use `event@{env}.php` file to override environment dependency value of `event.php`
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
    | Event Configuration
    |==============================================================================================
    | This section defines Event settings.
    | You may add event listeners as required.
    |
    | The Rebet event listener will be dispatched by class/type hinting that first argument of
    | listeners. There are no restrictions on the Event class, and you can create/use any class.
    | You can also use the (marker) interface to group events. These events can be listen together
    | by using interface as a type hint.
    |
    | Predefined Events:
    |  - Auth Events:
    |    - Individual Event:
    |      - Rebet\Auth\Event\SigninFailed       : Dispatched when signin failed.
    |      - Rebet\Auth\Event\Signined           : Dispatched when signin success.
    |      - Rebet\Auth\Event\Signouted          : Dispatched when signout.
    |      - Rebet\Auth\Event\AuthenticateFailed : authenticate failed (exclude Guest user).
    |      - Rebet\Auth\Event\Authenticated      : authenticate success (exclude Guest user).
    |    - Event Group:
    |      - Rebet\Auth\Event\Authentication     : Group that all of auth events.
    |  - Database Events:
    |    - Individual Event:
    |      - Rebet\Database\Event\Creating       : Dispatched when before data create (only via Database::create()).
    |      - Rebet\Database\Event\Created        : Dispatched when after data created (only via Database::create()).
    |      - Rebet\Database\Event\Updating       : Dispatched when before data update (only via Database::update()).
    |      - Rebet\Database\Event\Updated        : Dispatched when after data updated (only via Database::update()).
    |      - Rebet\Database\Event\Deleting       : Dispatched when before data delete (only via Database::delete()).
    |      - Rebet\Database\Event\Deleted        : Dispatched when after data deleted (only via Database::delete()).
    |      - Rebet\Database\Event\BatchUpdating  : Dispatched when before batch update (only via Database::updateBy()).
    |      - Rebet\Database\Event\BatchUpdated   : Dispatched when after batch updated (only via Database::updateBy()).
    |      - Rebet\Database\Event\BatchDeleting  : Dispatched when before batch delete (only via Database::deleteBy()).
    |      - Rebet\Database\Event\BatchDeleted   : Dispatched when after batch deleted (only via Database::deleteBy()).
    |    - Event Group:
    |      - Rebet\Database\Event\Saving         : Group that Creating, Updating, Deleting, BatchUpdating and BatchDeleting.
    |      - Rebet\Database\Event\Saved          : Group that Created, Updated, Deleted, BatchUpdated and BatchDeleted.
    |  - And any other events and groups you want:
    |    - Create your event class and dispatch by `Event::dispatch(new YourEvent(params, ...))`.
    */
    Event::class => [
        /*
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Event Listeners
        |~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        | Here you can add event listeners.
        | An event listener must be
        |  - Class that have `handle(EventClass $event)` method
        |  - Callable with type hinting of event class like `function(EventClass $event) { ... }`
        */
        'listeners' => [
            // YourEventListener::class or function(Event $event) { ... }
        ],
    ],
];
