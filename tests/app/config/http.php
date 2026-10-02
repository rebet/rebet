<?php

declare(strict_types=1);

use Rebet\Http\Session\Session;
use Rebet\Http\Session\Storage\ArraySessionStorage;

return [
    Session::class => [
        'storage' => ArraySessionStorage::class,
    ],
];
