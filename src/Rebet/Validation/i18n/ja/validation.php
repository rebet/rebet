<?php
/**
 * Validation error messages for Japanese.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
return [
    "@delimiter"      => "／",

    "Required"        => ":attributeを入力して下さい。",
    "RequiredIf"      => ":otherが:valueの場合は:attributeを入力して下さい。",
    "RequiredUnless"  => ":otherが:value以外の場合は:attributeを入力して下さい。",
    "RequiredWith"    => [
        "{some} :otherが:at_least項目以上入力されている場合は:attributeを入力して下さい。",
        "{*}    :otherが入力されている場合は:attributeを入力して下さい。",
    ],
    "RequiredWithout" => [
        "{some} :otherが:at_least項目以上入力されていない場合は:attributeを入力して下さい。",
        "{*}    :otherが入力されていない場合は:attributeを入力して下さい。",
    ],
    "BlankIf"         => ":otherが:valueの場合は:attributeを空にして下さい。",
    "BlankUnless"     => ":otherが:value以外の場合は:attributeを空にして下さい。",
    "BlankWith"       => [
        "{some} :otherが:at_least項目以上入力されている場合は:attributeを空にして下さい。",
        "{*}    :otherが入力されている場合は:attributeを空にして下さい。",
    ],
    "BlankWithout"    => [
        "{some} :otherが:at_least項目以上入力されていない場合は:attributeを空にして下さい。",
        "{*}    :otherが入力されていない場合は:attributeを空にして下さい。",
    ],
    "SameAs"          => ":attributeの値が:valueと一致しません。",
    "NotSameAs"       => ":attributeの値が:valueと一致してはなりません。",
    "Regex"           => [
        "{*} :attributeの書式が正しくありません。",
    ],
    "Regex@List"      => [
        "{*} :nth番目の:attribute（:value）の書式が正しくありません。",
    ],
    "NotRegex"        => [
        "{*} :attributeの書式が正しくありません。",
    ],
    "NotRegex@List"   => [
        "{*} :nth番目の:attribute（:value）の書式が正しくありません。",
    ],
    "MaxLength"       => ":attributeは:max文字以下で入力して下さい。",
    "MaxLength@List"  => ":nth番目の:attribute（:value）は:max文字以下で入力して下さい。",
    "MinLength"       => ":attributeは:min文字以上で入力して下さい。",
    "MinLength@List"  => ":nth番目の:attribute（:value）は:min文字以上で入力して下さい。",
    "Length"          => ":attributeは:min文字で入力して下さい。",
    "Length@List"     => ":nth番目の:attribute（:value）は:min文字で入力して下さい。",
    "Numeric"         => ":attributeは数値で入力して下さい。",
    "Numeric@List"    => ":nth番目の:attribute（:value）は数値で入力して下さい。",

];
