<?php

use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Pagination\Storage\ArrayCursorStorage;

return [
    Cursor::class => [
        'storage' => ArrayCursorStorage::class,
    ],

    Pager::class => [
        'resolver' => function (Pager $pager) { return $pager; }
    ],
];
