<?php
/**
 * Validation error messages for English.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
return [
    "Required"        => "The :attribute field is required.",
    "RequiredIf"      => [
        "[1]   The :attribute field is required when :other is :value.",
        "[2,*] The :attribute field is required when :other is in :value.",
    ],
    "RequiredUnless"  => [
        "[1]   The :attribute field is required when :other is not :value.",
        "[2,*] The :attribute field is required when :other is not in :value.",
    ],
    "RequiredWith"    => [
        "{one}  The :attribute field is required when :other is present.",
        "{some} The :attribute field is required when :other are present at least :at_least.",
        "{all}  The :attribute field is required when :other are present.",
    ],
    "RequiredWithout" => [
        "{one}  The :attribute field is required when :other is not present.",
        "{some} The :attribute field is required when :other are not present at least :at_least.",
        "{all}  The :attribute field is required when :other are not present.",
    ],
    "BlankIf"         => [
        "[1]   The :attribute field must be blank when :other is :value.",
        "[2,*] The :attribute field must be blank when :other is in :value.",
    ],
    "BlankUnless"     => [
        "[1]   The :attribute field must be blank when :other is not :value.",
        "[2,*] The :attribute field must be blank when :other is not in :value.",
    ],
    "BlankWith"       => [
        "{one}  The :attribute field must be blank when :other is present.",
        "{some} The :attribute field must be blank when :other are present at least :at_least.",
        "{all}  The :attribute field must be blank when :other are present.",
    ],
    "BlankWithout"    => [
        "{one}  The :attribute field must be blank when :other is not present.",
        "{some} The :attribute field must be blank when :other are not present at least :at_least.",
        "{all}  The :attribute field must be blank when :other are not present.",
    ],
    "SameAs"          => "The :attribute and :value must match.",
    "NotSameAs"       => "The :attribute and :value must not match.",
    "Regex"           => [
        "{*} The :attribute format is invalid.",
    ],
    "NotRegex"         => [
        "{*} The :attribute format is invalid.",
    ],

    "MaxLength"  => "The :attribute may not be greater than :max characters.",
];
