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
    "@delimiter"                    => ", ",
    "@nested_attribute_format"      => ":attribute :nested_attribute",
    
    "Required"                      => "The :attribute field is required.",
    "RequiredIf"                    => [
        "[1]   The :attribute field is required when :other is :value.",
        "[2,*] The :attribute field is required when :other is in :value.",
    ],
    "RequiredUnless"                => [
        "[1]   The :attribute field is required when :other is not :value.",
        "[2,*] The :attribute field is required when :other is not in :value.",
    ],
    "RequiredWith"                  => [
        "{one}  The :attribute field is required when :other is present.",
        "{some} The :attribute field is required when :other are present at least :at_least.",
        "{all}  The :attribute field is required when :other are present.",
    ],
    "RequiredWithout"               => [
        "{one}  The :attribute field is required when :other is not present.",
        "{some} The :attribute field is required when :other are not present at least :at_least.",
        "{all}  The :attribute field is required when :other are not present.",
    ],
    "BlankIf"                       => [
        "[1]   The :attribute field must be blank when :other is :value.",
        "[2,*] The :attribute field must be blank when :other is in :value.",
    ],
    "BlankUnless"                   => [
        "[1]   The :attribute field must be blank when :other is not :value.",
        "[2,*] The :attribute field must be blank when :other is not in :value.",
    ],
    "BlankWith"                     => [
        "{one}  The :attribute field must be blank when :other is present.",
        "{some} The :attribute field must be blank when :other are present at least :at_least.",
        "{all}  The :attribute field must be blank when :other are present.",
    ],
    "BlankWithout"                  => [
        "{one}  The :attribute field must be blank when :other is not present.",
        "{some} The :attribute field must be blank when :other are not present at least :at_least.",
        "{all}  The :attribute field must be blank when :other are not present.",
    ],
    "SameAs"                        => "The :attribute and :value must match.",
    "NotSameAs"                     => "The :attribute and :value must not match.",
    "Regex"                         => [
        "{*} The :attribute format is invalid.",
    ],
    "Regex@List"                   => [
        "{*} The :nth :attribute (:value) format is invalid.",
    ],
    "NotRegex"                      => [
        "{*} The :attribute format is invalid.",
    ],
    "NotRegex@List"                 => [
        "{*} The :nth :attribute (:value) format is invalid.",
    ],
    "MaxLength"                     => "The :attribute may not be greater than :max characters.",
    "MaxLength@List"                => "The :nth :attribute (:value) may not be greater than :max characters.",
    "MinLength"                     => "The :attribute must be at least :min characters.",
    "MinLength@List"                => "The :nth :attribute (:value) must be at least :min characters.",
    "Length"                        => "The :attribute must be :length characters.",
    "Length@List"                   => "The :nth :attribute (:value) must be :length characters.",
    "Number"                        => "The :attribute must be number.",
    "Number@List"                   => "The :nth :attribute (:value) must be number.",
    "Integer"                       => "The :attribute must be integer.",
    "Integer@List"                  => "The :nth :attribute (:value) must be integer.",
    "Float"                         => "The :attribute must be real number (up to :decimal decimal places).",
    "Float@List"                    => "The :nth :attribute (:value) must be real number (up to :decimal decimal places).",
    "NumberLessThan"                => "The :attribute must be less than :number.",
    "NumberLessThan@List"           => "The :nth :attribute (:value) must be less than :number.",
    "NumberLessThanOrEqual"         => "The :attribute may not be greater than :number.",
    "NumberLessThanOrEqual@List"    => "The :nth :attribute (:value) may not be greater than :number.",
    "NumberGreaterThan"             => "The :attribute must be greater than :number.",
    "NumberGreaterThan@List"        => "The :nth :attribute (:value) must be greater than :number.",
    "NumberGreaterThanOrEqual"      => "The :attribute must be at least :number.",
    "NumberGreaterThanOrEqual@List" => "The :nth :attribute (:value) must be at least :number.",
    "Email"                         => "The :attribute must be a valid email address.",
    "Email@List"                    => "The :nth :attribute (:value) must be a valid email address.",
    "Url"                           => [
        "{nonactive} The :attribute is not a valid URL.",
        "{*}         The :attribute format is invalid.",
    ],
    "Url@List"                      => [
        "{nonactive} The :nth :attribute (:value) is not a valid URL.",
        "{*}         The :nth :attribute (:value) format is invalid.",
    ],
    "Ipv4"                          => "The :attribute must be a valid IPv4(CIDR) address.",
    "Ipv4@List"                     => "The :nth :attribute (:value) must be a valid IPv4(CIDR) address.",
    "Digit"                         => "The :attribute may only contain digits.",
    "Digit@List"                    => "The :nth :attribute (:value) may only contain digits.",
    "Alpha"                         => "The :attribute may only contain letters.",
    "Alpha@List"                    => "The :nth :attribute (:value) may only contain letters.",
    "AlphaDigit"                    => "The :attribute may only contain letters or digits.",
    "AlphaDigit@List"               => "The :nth :attribute (:value) may only contain letters or digits.",
    "AlphaDigitMark"                => "The :attribute may only contain letters, digits or marks (include :mark).",
    "AlphaDigitMark@List"           => "The :nth :attribute (:value) may only contain letters, digits or marks (include :mark).",
    "Hiragana"                      => "The :attribute may only contain Hiragana in Japanese.",
    "Hiragana@List"                 => "The :nth :attribute (:value) may only contain Hiragana in Japanese.",
    "Kana"                          => "The :attribute may only contain full width Kana in Japanese.",
    "Kana@List"                     => "The :nth :attribute (:value) may only contain full width Kana in Japanese.",
    "DependenceChar"                => "The :attribute must not contain platform dependent character [:dependences].",
    "DependenceChar@List"           => "The :nth :attribute (:value) must not contain platform dependent character [:dependences].",
    "NgWord"                        => "The :attribute must not contain the word ':ng_word'.",
    "NgWord@List"                   => "The :nth :attribute (:value) must not contain the word ':ng_word'.",
    "Contains"                      => "The :attribute must be selected from the specified list.",
    "Contains@List"                 => "The :nth :attribute must be selected from the specified list.",
    "MinCount"                      => [
        "[1]   The :attribute must have at least :min item.",
        "[2,*] The :attribute must have at least :min items.",
    ],
    "MaxCount"                      => [
        "[1]   The :attribute may not have more than :max item.",
        "[2,*] The :attribute may not have more than :max items.",
    ],
    "Count"                         => [
        "[1]   The :attribute must have :count item.",
        "[2,*] The :attribute must have :count items.",
    ],
    "Unique"                        => [
        "[1]   The :attribute must be entered a different value. [:duplicate] was duplicated.",
        "[2,*] The :attribute must be entered a different value. [:duplicate] were duplicated.",
    ],
    "Datetime"                      => "The :attribute is not a valid date time.",
    "Datetime@List"                 => "The :nth :attribute (:value) is not a valid date time.",
    "FutureThan"                    => "The :attribute must be a date future than :at_time.",
    "FutureThan@List"               => "The :nth :attribute (:value) must be a date future than :at_time.",
    "FutureThanOrEqual"             => "The :attribute must be a date future than or equal :at_time.",
    "FutureThanOrEqual@List"        => "The :nth :attribute (:value) must be a date future than or equal :at_time.",
    "PastThan"                      => "The :attribute must be a date past than :at_time.",
    "PastThan@List"                 => "The :nth :attribute (:value) must be a date past than :at_time.",
    "PastThanOrEqual"               => "The :attribute must be a date past than or equal :at_time.",
    "PastThanOrEqual@List"          => "The :nth :attribute (:value) must be a date past than or equal :at_time.",
    "MaxAge"                        => "The :attribute must be a date past than or equal :at_time.",
    "MaxAge@List"                   => "The :nth :attribute (:value) must be a date past than or equal :at_time.",
    "MaxAge"                        => [
        "{today,now} The age must be :max years or younger.",
        "{*}         The age must be :max years or younger as of :at_time.",
    ],
    "MaxAge@List"                   => [
        "{today,now} The :nth value (:value) of :attribute must be :max years or younger.",
        "{*}         The :nth value (:value) of :attribute must be :max years or younger as of :at_time.",
    ],
    "MinAge"                        => [
        "{today,now} The age must be :min years or older.",
        "{*}         The age must be :min years or older as of :at_time.",
    ],
    "MinAge@List"                   => [
        "{today,now} The :nth value (:value) of :attribute must be :min years or older.",
        "{*}         The :nth value (:value) of :attribute must be :min years or older as of :at_time.",
    ],
    

];
