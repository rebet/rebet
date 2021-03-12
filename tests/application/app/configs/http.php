<?php

use Rebet\Http\Session\Session;
use Rebet\Http\Session\Storage\ArraySessionStorage;

return [
    Session::class => [
        'storage' => ArraySessionStorage::class,
    ],
];
