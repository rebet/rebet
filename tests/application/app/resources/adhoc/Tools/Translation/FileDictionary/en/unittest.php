<?php
return [
    "@delimiter" => ", ",
    "@full_name" => ":first_name :last_name",

    "hello"  => "Hello Rebet.",
    "welcom" => "Welcom :name !",

    "select_by_number" => [
        "[1] This is 1.",
        "[2] This is 2.",
        "[3] This is 3.",
        "[*] This is *(othre).",
    ],
    "select_by_number_using_pipe" => "[1] This is 1.|[2] This is 2.|[3] This is 3.|[*] This is *(othre).",

    "select_by_number_range" => [
        "[*,9]   This is less than or equal 9.",
        "[10,19] This is 10 to 19.",
        "[20,*]  This is greater than or equal 20.",
        "[*,*]   This is *,*(othre).",
    ],
    "select_by_number_without_other" => [
        "[1] This is 1.",
    ],
    "select_by_word" => [
        "{one}  This is one.",
        "{some} This is some.",
        "{all}  This is all.",
        "{*}    This is *(othre).",
    ],
    "select_by_word_multi" => [
        "{today,now} This is today or now.",
        "{*}         This is *(othre).",
    ],
    "select_by_word_withot_other" => [
        "{one} This is one.",
    ],

    "recursive"          => 'root',
    "a.recursive"        => 'a',
    "b.recursive"        => 'b',
    "custom.a.recursive" => 'A',

    "group" => [
        "parent" => [
            "child" => "[1] foo|[2] bar|[*] baz",
        ],
    ],
];
