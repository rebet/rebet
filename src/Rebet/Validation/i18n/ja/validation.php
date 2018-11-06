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
    "@delimiter"                    => "／",
    "@nested_attribute_format"      => ":attributeの:nested_attribute",

    "Required"                      => ":attributeを入力して下さい。",
    "RequiredIf"                    => ":otherが:valueの場合は:attributeを入力して下さい。",
    "RequiredUnless"                => ":otherが:value以外の場合は:attributeを入力して下さい。",
    "RequiredWith"                  => [
        "{some} :otherが:at_least項目以上入力されている場合は:attributeを入力して下さい。",
        "{*}    :otherが入力されている場合は:attributeを入力して下さい。",
    ],
    "RequiredWithout"               => [
        "{some} :otherが:at_least項目以上入力されていない場合は:attributeを入力して下さい。",
        "{*}    :otherが入力されていない場合は:attributeを入力して下さい。",
    ],
    "BlankIf"                       => ":otherが:valueの場合は:attributeを空にして下さい。",
    "BlankUnless"                   => ":otherが:value以外の場合は:attributeを空にして下さい。",
    "BlankWith"                     => [
        "{some} :otherが:at_least項目以上入力されている場合は:attributeを空にして下さい。",
        "{*}    :otherが入力されている場合は:attributeを空にして下さい。",
    ],
    "BlankWithout"                  => [
        "{some} :otherが:at_least項目以上入力されていない場合は:attributeを空にして下さい。",
        "{*}    :otherが入力されていない場合は:attributeを空にして下さい。",
    ],
    "SameAs"                        => ":attributeの値が:valueと一致しません。",
    "NotSameAs"                     => ":attributeの値が:valueと一致してはなりません。",
    "Regex"                         => [
        "{*} :attributeの書式が正しくありません。",
    ],
    "Regex@List"                    => [
        "{*} :nth番目の:attribute(:value)の書式が正しくありません。",
    ],
    "NotRegex"                      => [
        "{*} :attributeの書式が正しくありません。",
    ],
    "NotRegex@List"                 => [
        "{*} :nth番目の:attribute(:value)の書式が正しくありません。",
    ],
    "MaxLength"                     => ":attributeは:max文字以下で入力して下さい。",
    "MaxLength@List"                => ":nth番目の:attribute(:value)は:max文字以下で入力して下さい。",
    "MinLength"                     => ":attributeは:min文字以上で入力して下さい。",
    "MinLength@List"                => ":nth番目の:attribute(:value)は:min文字以上で入力して下さい。",
    "Length"                        => ":attributeは:min文字で入力して下さい。",
    "Length@List"                   => ":nth番目の:attribute(:value)は:min文字で入力して下さい。",
    "Number"                        => ":attributeは数値で入力して下さい。",
    "Number@List"                   => ":nth番目の:attribute(:value)は数値で入力して下さい。",
    "Integer"                       => ":attributeは整数で入力して下さい。",
    "Integer@List"                  => ":nth番目の:attribute(:value)は整数で入力して下さい。",
    "Float"                         => ":attributeは実数(小数点:scale桁まで)で入力して下さい。",
    "Float@List"                    => ":nth番目の:attribute(:value)は実数(小数点:scale桁まで)で入力して下さい。",
    "NumberLessThan"                => ":attributeは:number未満で入力して下さい。",
    "NumberLessThan@List"           => ":nth番目の:attribute(:value)は:number未満で入力して下さい。",
    "NumberLessThanOrEqual"         => ":attributeは:number以下で入力して下さい。",
    "NumberLessThanOrEqual@List"    => ":nth番目の:attribute(:value)は:number以下で入力して下さい。",
    "NumberGreaterThan"             => ":attributeは:numberより上で入力して下さい。",
    "NumberGreaterThan@List"        => ":nth番目の:attribute(:value)は:numberより上で入力して下さい。",
    "NumberGreaterThanOrEqual"      => ":attributeは:number以上で入力して下さい。",
    "NumberGreaterThanOrEqual@List" => ":nth番目の:attribute(:value)は:number以上で入力して下さい。",
    "Email"                         => ":attributeはメールアドレス形式で入力して下さい。",
    "Email@List"                    => ":nth番目の:attribute(:value)はメールアドレス形式で入力して下さい。",
    "Url"                           => [
        "{nonactive} :attributeは有効なURLではありません。",
        "{*}         :attributeはURL形式で入力して下さい。",
    ],
    "Url@List"                      => [
        "{nonactive} :nth番目の:attribute(:value)は有効なURLではありません。",
        "{*}         :nth番目の:attribute(:value)はURL形式で入力して下さい。",
    ],
    "Ipv4"                          => ":attributeはIPv4(CIDR)形式で入力して下さい。",
    "Ipv4@List"                     => ":nth番目の:attribute(:value)はIPv4(CIDR)形式で入力して下さい。",
    "Digit"                         => ":attributeは半角数字で入力して下さい。",
    "Digit@List"                    => ":nth番目の:attribute(:value)は半角数字で入力して下さい。",
    "Alpha"                         => ":attributeは半角英字で入力して下さい。",
    "Alpha@List"                    => ":nth番目の:attribute(:value)は半角英字で入力して下さい。",
    "AlphaDigit"                    => ":attributeは半角英数字で入力して下さい。",
    "AlphaDigit@List"               => ":nth番目の:attribute(:value)は半角英数字で入力して下さい。",
    "AlphaDigitMark"                => ":attributeは半角英数記号(:markを含む)で入力して下さい。",
    "AlphaDigitMark@List"           => ":nth番目の:attribute(:value)は半角英数記号(:markを含む)で入力して下さい。",
    "Hiragana"                      => ":attributeはひらがなで入力して下さい。",
    "Hiragana@List"                 => ":nth番目の:attribute(:value)はひらがなで入力して下さい。",
    "Kana"                          => ":attributeは全角カタカナで入力して下さい。",
    "Kana@List"                     => ":nth番目の:attribute(:value)は全角カタカナで入力して下さい。",
    "DependenceChar"                => ":attributeは機種依存文字 [:dependences] を含まないで下さい。",
    "DependenceChar@List"           => ":nth番目の:attribute(:value)は機種依存文字 [:dependences] を含まないで下さい。",
    "NgWord"                        => ":attributeに利用できない単語「:ng_word」が含まれます。",
    "NgWord@List"                   => ":nth番目の:attribute(:value)に利用できない単語「:ng_word」が含まれます。",
    "Contains"                      => ":attributeは指定の一覧から選択して下さい。",
    "Contains@List"                 => ":nth番目の:attribute(:value)は指定の一覧から選択して下さい。",
    "MinCount"                      => ":attributeは:min個以上選択して下さい。",
    "MaxCount"                      => ":attributeは:max個以下で選択して下さい。",
    "Count"                         => ":attributeは:count個選択して下さい。",
    "Unique"                        => ":attributeには異なる値を入力して下さい。[:duplicate] が重複しています。",
    "Datetime"                      => ":attributeは正しい日付／日時形式で入力して下さい。",
    "Datetime@List"                 => ":nth番目の:attributeは正しい日付／日時形式で入力して下さい。",
    "FutureThan"                    => ":attributeは:at_timeよりも未来の日付を入力して下さい。",
    "FutureThan@List"               => ":nth番目の:attributeは:at_timeよりも未来の日付を入力して下さい。",
    "FutureThanOrEqual"             => ":attributeは:at_timeよりも未来の日付(指定日時を含む)を入力して下さい。",
    "FutureThanOrEqual@List"        => ":nth番目の:attributeは:at_timeよりも未来の日付(指定日時を含む)を入力して下さい。",
    "PastThan"                      => ":attributeは:at_timeよりも過去の日付を入力して下さい。",
    "PastThan@List"                 => ":nth番目の:attributeは:at_timeよりも過去の日付を入力して下さい。",
    "MaxAge"                        => [
        "{today,now} :現在の年齢は:max歳以下でなければなりません。",
        "{*}         :at_time時点の年齢は:max歳以下でなければなりません。",
    ],
    "MaxAge@List"                   => [
        "{today,now} :nth番目の現在の年齢は:max歳以下でなければなりません。",
        "{*}         :nth番目の:at_time時点の年齢は:max歳以下でなければなりません。",
    ],
    "MinAge"                        => [
        "{today,now} :現在の年齢は:min歳以上でなければなりません。",
        "{*}         :at_time時点の年齢は:min歳以上でなければなりません。",
    ],
    "MinAge@List"                   => [
        "{today,now} :nth番目の現在の年齢は:min歳以上でなければなりません。",
        "{*}         :nth番目の:at_time時点の年齢は:min歳以上でなければなりません。",
    ],
    "SequentialNumber"              => ":attributeは連番でなければなりません。",
    "Accepted"                      => ":attributeに同意して下さい。",

];
