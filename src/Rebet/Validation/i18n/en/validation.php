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
    "RequiredWith"    => "The ':label' field is required when :other are present at least :at_least.",
    "RequiredWithout" => "The ':label' field is required when :other are not present at least :at_least.",
    "BlankIf"         => [
        "[1]   The ':label' field must be blank when :other is :value.",
        "[2,*] The ':label' field must be blank when :other is in :value.",
    ],
    "BlankUnless"     => [
        "[1]   The ':label' field must be blank when :other is not :value.",
        "[2,*] The ':label' field must be blank when :other is not in :value.",
    ],
    "BlankWith"       => "The ':label' field must be blank when :other are present at least :at_least.",
    "BlankWithout"    => "The ':label' field must be blank when :other are not present at least :at_least.",

    "LengthMax"  => "The ':label' may not be greater than :max characters.",
];
