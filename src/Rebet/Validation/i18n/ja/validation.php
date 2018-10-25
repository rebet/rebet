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
    "SameAs"          => ":attributeの値が:valueと異なります。",

    "MaxLength"  => ":attributeは:max文字以下で入力して下さい。",
];
