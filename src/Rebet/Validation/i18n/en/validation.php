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
    "Required"        => "The ':label' field is required.",
    "RequiredIf"      => [
        "[1]   The ':label' field is required when :other is :value.",
        "[2,*] The ':label' field is required when :other is in :value.",
    ],
    "RequiredUnless"  => [
        "[1]   The ':label' field is required when :other is not :value.",
        "[2,*] The ':label' field is required when :other is not in :value.",
    ],
    "RequiredWith"    => [
        "{one}  The ':label' field is required when :other is present.",
        "{some} The ':label' field is required when :other are present at least :at_least.",
        "{all}  The ':label' field is required when :other are present.",
    ],
    "RequiredWithout" => [
        "{one}  The ':label' field is required when :other is not present.",
        "{some} The ':label' field is required when :other are not present at least :at_least.",
        "{all}  The ':label' field is required when :other are not present.",
    ],
    "BlankIf"         => [
        "[1]   The ':label' field must be blank when :other is :value.",
        "[2,*] The ':label' field must be blank when :other is in :value.",
    ],
    "BlankUnless"     => [
        "[1]   The ':label' field must be blank when :other is not :value.",
        "[2,*] The ':label' field must be blank when :other is not in :value.",
    ],
    "BlankWith"       => [
        "{one}  The ':label' field must be blank when :other is present.",
        "{some} The ':label' field must be blank when :other are present at least :at_least.",
        "{all}  The ':label' field must be blank when :other are present.",
    ],
    "BlankWithout"    => [
        "{one}  The ':label' field must be blank when :other is not present.",
        "{some} The ':label' field must be blank when :other are not present at least :at_least.",
        "{all}  The ':label' field must be blank when :other are not present.",
    ],

    "LengthMax"  => "The ':label' may not be greater than :max characters.",
];
