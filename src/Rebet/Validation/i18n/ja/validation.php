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
    "@delimiter"       => "／",

    "Required"         => ":attributeを入力して下さい。",
    "RequiredIf"       => ":otherが:valueの場合は:attributeを入力して下さい。",
    "RequiredUnless"   => ":otherが:value以外の場合は:attributeを入力して下さい。",
    "RequiredWith"     => [
        "{some} :otherが:at_least項目以上入力されている場合は:attributeを入力して下さい。",
        "{*}    :otherが入力されている場合は:attributeを入力して下さい。",
    ],
    "RequiredWithout"  => [
        "{some} :otherが:at_least項目以上入力されていない場合は:attributeを入力して下さい。",
        "{*}    :otherが入力されていない場合は:attributeを入力して下さい。",
    ],
    "BlankIf"          => ":otherが:valueの場合は:attributeを空にして下さい。",
    "BlankUnless"      => ":otherが:value以外の場合は:attributeを空にして下さい。",
    "BlankWith"        => [
        "{some} :otherが:at_least項目以上入力されている場合は:attributeを空にして下さい。",
        "{*}    :otherが入力されている場合は:attributeを空にして下さい。",
    ],
    "BlankWithout"     => [
        "{some} :otherが:at_least項目以上入力されていない場合は:attributeを空にして下さい。",
        "{*}    :otherが入力されていない場合は:attributeを空にして下さい。",
    ],
    "SameAs"           => ":attributeの値が:valueと一致しません。",
    "NotSameAs"        => ":attributeの値が:valueと一致してはなりません。",
    "Regex"            => [
        "{*} :attributeの書式が正しくありません。",
    ],
    "Regex@List"       => [
        "{*} :nth番目の:attribute(:value)の書式が正しくありません。",
    ],
    "NotRegex"         => [
        "{*} :attributeの書式が正しくありません。",
    ],
    "NotRegex@List"    => [
        "{*} :nth番目の:attribute(:value)の書式が正しくありません。",
    ],
    "MaxLength"        => ":attributeは:max文字以下で入力して下さい。",
    "MaxLength@List"   => ":nth番目の:attribute(:value)は:max文字以下で入力して下さい。",
    "MinLength"        => ":attributeは:min文字以上で入力して下さい。",
    "MinLength@List"   => ":nth番目の:attribute(:value)は:min文字以上で入力して下さい。",
    "Length"           => ":attributeは:min文字で入力して下さい。",
    "Length@List"      => ":nth番目の:attribute(:value)は:min文字で入力して下さい。",
    "Number"           => ":attributeは数値で入力して下さい。",
    "Number@List"      => ":nth番目の:attribute(:value)は数値で入力して下さい。",
    "Integer"          => ":attributeは整数で入力して下さい。",
    "Integer@List"     => ":nth番目の:attribute(:value)は整数で入力して下さい。",
    "Float"            => ":attributeは実数(小数点:scale桁まで)で入力して下さい。",
    "Float@List"       => ":nth番目の:attribute(:value)は実数(小数点:scale桁まで)で入力して下さい。",
    "MaxNumber"        => ":attributeは:max以下で入力して下さい。",
    "MaxNumber@List"   => ":nth番目の:attribute(:value)は:max以下で入力して下さい。",
    "MinNumber"        => ":attributeは:min以上で入力して下さい。",
    "MinNumber@List"   => ":nth番目の:attribute(:value)は:min以上で入力して下さい。",
    "Email"            => ":attributeはメールアドレス形式で入力して下さい。",
    "Email@List"       => ":nth番目の:attribute(:value)はメールアドレス形式で入力して下さい。",
    "Url"              => [
        "{nonactive} :attributeは有効なURLではありません。",
        "{*}         :attributeはURL形式で入力して下さい。",
    ],
    "Url@List"         => [
        "{nonactive} :nth番目の:attribute(:value)は有効なURLではありません。",
        "{*}         :nth番目の:attribute(:value)はURL形式で入力して下さい。",
    ],
    "Ipv4"             => ":attributeはIPv4(CIDR)形式で入力して下さい。",
    "Ipv4@List"        => ":nth番目の:attribute(:value)はIPv4(CIDR)形式で入力して下さい。",
    "Digit"            => ":attributeは半角数字で入力して下さい。",
    "Digit@List"       => ":nth番目の:attribute(:value)は半角数字で入力して下さい。",
    "Alpha"            => ":attributeは半角英字で入力して下さい。",
    "Alpha@List"       => ":nth番目の:attribute(:value)は半角英字で入力して下さい。",

];
