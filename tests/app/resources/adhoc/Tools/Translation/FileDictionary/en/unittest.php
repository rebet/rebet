<?php

declare(strict_types=1);

return [
    "@delimiter"                              => ", ",
    "@full_name"                              => ":first_name :last_name",

    "hello"                                   => "Hello Rebet.",
    "welcom"                                  => "Welcom :name !",

    "select_by_number"                        => [
        "[1] This is 1.",
        "[2] This is 2.",
        "[3] This is 3.",
        "[*] This is *(othre).",
    ],
    "select_by_number_using_pipe"             => "[1] This is 1.|[2] This is 2.|[3] This is 3.|[*] This is *(othre).",

    "select_by_number_range"                  => [
        "[*,9]   This is less than or equal 9.",
        "[10,19] This is 10 to 19.",
        "[20,*]  This is greater than or equal 20.",
        "[*,*]   This is *,*(othre).",
    ],
    "select_by_number_without_other"          => [
        "[1] This is 1.",
    ],
    "select_by_word"                          => [
        "{one}  This is one.",
        "{some} This is some.",
        "{all}  This is all.",
        "{*}    This is *(othre).",
    ],
    "select_by_word_multi"                    => [
        "{today,now} This is today or now.",
        "{*}         This is *(othre).",
    ],
    "select_by_word_withot_other"             => [
        "{one} This is one.",
    ],

    "recursive"                               => 'root',
    "a.recursive"                             => 'a',
    "b.recursive"                             => 'b',
    "custom.a.recursive"                      => 'A',

    "plain_pipe"                              => "first|second",
    "select_by_word_numeric"                  => [
        "{1} This is one.",
        "{*} This is *(othre).",
    ],
    "select_by_number_without_wildcard_range" => [
        "[1,5] This is 1 to 5.",
    ],
    "empty_in_ja"                             => "Fallback to en.",
    "zero_in_ja"                              => "Fallback to en.",

    "dotted.key"                              => "literal",
    "dotted"                                  => [
        "key"   => "nested",
        "other" => "nested other",
    ],
    "order"                                   => [
        "target" => "en full",
    ],

    "group"                                   => [
        "parent" => [
            "child" => "[1] foo|[2] bar|[*] baz",
        ],
    ],
];
