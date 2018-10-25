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
    "@delimiter"      => ", ",
    
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
    "Regex@List"      => [
        "{*} The :nth :attribute (:value) format is invalid.",
    ],
    "NotRegex"        => [
        "{*} The :attribute format is invalid.",
    ],
    "NotRegex@List"   => [
        "{*} The :nth :attribute (:value) format is invalid.",
    ],
    "MaxLength"       => "The :attribute may not be greater than :max characters.",
    "MaxLength@List"  => "The :nth :attribute (:value) may not be greater than :max characters.",
    "MinLength"       => "The :attribute must be at least :min characters.",
    "MinLength@List"  => "The :nth :attribute (:value) must be at least :min characters.",
    "Length"          => "The :attribute must be :length characters.",
    "Length@List"     => "The :nth :attribute (:value) must be :length characters.",
    "Number"          => "The :attribute must be number.",
    "Number@List"     => "The :nth :attribute (:value) must be number.",
    "Integer"         => "The :attribute must be integer.",
    "Integer@List"    => "The :nth :attribute (:value) must be integer.",
    "Float"           => "The :attribute must be real number (up to :decimal decimal places).",
    "Float@List"      => "The :nth :attribute (:value) must be real number (up to :decimal decimal places).",

];
