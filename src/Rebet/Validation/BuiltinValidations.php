<?php
namespace Rebet\Validation;

use Rebet\Common\Arrays;
use Rebet\Common\Strings;
use Rebet\Common\System;
use Rebet\Common\Utils;
use Rebet\Config\Config;
use Rebet\Config\Configurable;
use Rebet\DateTime\DateTime;
use Rebet\File\Files;
use Rebet\Translation\FileLoader;
use Rebet\Translation\Translator;

/**
 * BuiltinValidations Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BuiltinValidations extends Validations
{
    use Configurable;

    public static function defaultConfig()
    {
        return static::parentConfigOverride([
            'resources' => [
                'i18n' => [Files::normalizePath(__DIR__ . '/i18n')],
            ],
            'default'   => [
                'DependenceChar' => [
                    'encode' => 'sjis-win'
                ],
                'NgWord' => [
                    'word_split_pattern' => '[\p{Z}\p{P}]',
                    'delimiter_pattern'  => '[\p{Common}]',
                    'omission_pattern'   => '[\p{M}\p{S}〇*＊_＿]',
                    'omission_length'    => 3,
                    'omission_ratio'     => 0.4,
                    'ambiguous_patterns' => [
                        "^" => "^",
                        "$" => "$",
                        // @todo 同位系の列挙 https://ja.wikipedia.org/wiki/A
                        "a" => "([aAꜸꜹꜺꜻꜼꜽꜲꜳⱯɐⱭɑɒẚÁáÀàĂăẮắẰằẴẵẲẳÂâẤấẦầẪẫẨẩǍǎÅåǺǻÄäǞǟÃãȦȧǠǡĄąĄ̈ą̈ĀāẢảȀȁȂȃẠạẶặẬậḀḁȺⱥᶏǼǽǢǣᶐΛａＡⒶⓐ🄰🅐🅰@＠🄐⒜])",
                        "b" => "([bBƄƅÞþẞßʙḂḃḄḅḆḇɃƀᵬᶀƁɓƂƃｂＢⒷⓑ🄱🅑🅱])",
                        "c" => "([cCƆɔↃↄꜾꜿĈĉČčĊċÇçḈḉȻȼƇƈɕｃＣⒸⓒ🄲🅒🅲©])",
                        "d" => "([dDȸĎďḊḋḐḑḌḍḒḓḎḏĐđÐðᵭᶁƉɖƊɗᶑƋƌȡｄＤⒹⓓ🄳🅓🅳])",
                        "e" => "([eEƎǝƏəƐɛɘɜɞʚÉéÈèĔĕÊêẾếỀềỄễỂểĚěËëẼẽĖėȨȩḜḝĘęĒēḖḗḔḕẺẻȄȅȆȇẸẹỆệḘḙḚḛɆɇᶒᶕɚᶓᶔɝｅＥⒺⓔ🄴🅔🅴])",
                        "f" => "([fFʩꝻꝼℲⅎḞḟᵮᶂƑƒｆＦⒻⓕ🄵🅕🅵])",
                        "g" => "([gGɡᵹɢʛᵷƔɣƢƣǴǵĞğĜĝǦǧĠġĢģḠḡǤǥᶃƓɠｇＧⒼⓖ🄶🅖🅶])",
                        "h" => "([hHʜǶƕɦⱵⱶɧĤĥȞȟḦḧḢḣḨḩḤḥḪḫH̱ẖĦħⱧⱨｈＨⒽⓗ🄷🅗🅷])",
                        "i" => "([iIɪƖɩÍíÌìĬĭÎîǏǐÏïḮḯĨĩİiĮįĪīỈỉȈȉȊȋỊịḬḭIıƗɨᵻᶖｉＩⒾⓘ🄸🅘🅸])",
                        "j" => "([jJĴĵJ̌ǰȷɈɉʝɟʄｊＪⒿⓙ🄹🅙🅹])",
                        "k" => "([kKĸʞḰḱǨǩĶķḲḳḴḵꝄꝅꝂꝃꝀꝁᶄƘƙⱩⱪｋＫⓀⓚ🄺🅚🅺])",
                        "l" => "([lLʟɮꞀꞁĹĺĽľĻļḶḷḸḹḼḽḺḻŁłŁ̣ł̣ĿŀȽƚⱠⱡⱢɫꝈꝉꝆꝇɬᶅɭȴｌＬⓁⓛ🄻🅛🅻])",
                        "m" => "([mMḾḿṀṁṂṃᵯᶆɱｍＭⓂⓜ🄼🅜🅼])",
                        "n" => "([nNɴŃńǸǹN̂n̂ŇňN̈n̈N̄n̄ÑñṄṅŅņṆṇṊṋṈṉᵰƝɲȠƞŊŋᶇɳȵｎＮⓃⓝ🄽🅝🅽])",
                        "o" => "([oOÓóÒòŎŏÔôỐốỒồỖỗỔổǑǒÖöȪȫŐőÕõṌṍṎṏȬȭȮȯȰȱØøǾǿǪǫǬǭŌōṒṓṐṑỎỏȌȍȎȏƠơỚớỜờỠỡỞởỢợỌọỘộƟɵꝊꝋꝌꝍ0ｏＯⓄⓞ🄾🅞🅾])",
                        "p" => "([pPǷƿṔṕṖṗⱣᵽꝐꝑᶈƤƥꝒꝓꝔꝕP̃p̃ꝤꝥꝦꝧｐＰⓅⓟ🄿🅟🅿℗])",
                        "q" => "([qQʠꝘꝙɊɋQ̊q̊Q̧q̧ｑｑＱⓆⓠ🅀🅠🆀])",
                        "r" => "([rRƦʀɹɺʁŔŕŘřṘṙŖŗȐȑȒȓṚṛṜṝṞṟɌɍᵲᶉɼꞂꞃⱤɽɾᵳｒＲⓇⓡ🅁🅡🆁®])",
                        "s" => "([sSŚśṤṥŜŝŠšṦṧṠṡŞşṢṣṨṩȘșᵴᶊʂȿS̩s̩ｓＳⓈⓢ🅂🅢🆂])",
                        "t" => "([tTꞄꞅᶋᶘŤťT̈ẗṪṫŢţṬṭȚțṰṱṮṯŦŧȾⱦᵵƫƬƭƮʈȶｔＴⓉⓣ🅃🅣🆃])",
                        "u" => "([uUÚúÙùŬŭÛûǓǔŮůÜüǗǘǛǜǙǚǕǖŰűŨũṸṹŲųŪūṺṻỦủȔȕȖȗƯưỨứỪừỮữỬửỰựỤụṲṳṶṷṴṵɄʉᵾᶙᵿｕＵⓊⓤ🅄🅤🆄])",
                        "v" => "([vVɅʌṼṽṾṿᶌƲʋⱴｖＶⓋⓥ🅅🅥🆅])",
                        "w" => "([wWƜʍɯẂẃẀẁŴŵW̊ẘẄẅẆẇẈẉꝠꝡｗＷⓌⓦ🅆🅦🆆])",
                        "x" => "([xXẌẍẊẋᶍｘＸⓍⓧ🅇🅧🆇])",
                        "y" => "([yYʎÝýỲỳŶŷY̊ẙŸÿỸỹẎẏȲȳỶỷỴỵʏɎɏƳƴｙＹⓎⓨ🅈🅨🆈])",
                        "z" => "([zZŹźẐẑŽžŻżẒẓẔẕƵƶᵶᶎȤȥʐʑɀⱫⱬǮǯᶚƺꝢꝣｚＺⓏⓩ🅉🅩🆉])",
                        "0" => "([0０⓿])",
                        "1" => "([1１①⓵❶➀➊㊀一壱壹弌🈩])",
                        "2" => "([2２②⓶❷➁➋㊁二弐貳弎🈔])",
                        "3" => "([3Ʒʒ３③⓷❸➂➌㊂三参參弎🈪])",
                        "4" => "([4４Ꝝꝝ④⓸❹➃➍㊃四肆])",
                        "5" => "([5Ƽƽ５⑤⓹❺➄➎㊄五伍])",
                        "6" => "([6６⑥⓺❻➅➏㊅六陸])",
                        "7" => "([7７⑦⓻❼➆➐㊆七漆柒質])",
                        "8" => "([8８⑧⓼❽➇➑㊇八捌])",
                        "9" => "([9９⑨⓽❾➈➒㊈九玖])",
                        'ア' => '([アｱ㋐あァｧぁ])',
                        'イ' => '([イｲ㋑㋼いィｨぃヰゐ])',
                        'ウ' => '([ウｳ㋒うゥｩぅヱゑ])',
                        'エ' => '([エｴ㋓㋽えェｪぇ])',
                        'オ' => '([オｵ㋔おォｫぉ])',
                        'カ' => '([カｶ㋕かヵゕ])',
                        'キ' => '([キｷ㋖き])',
                        'ク' => '([クｸ㋗く])',
                        'ケ' => '([ケｹ㋘けヶ])',
                        'コ' => '([コｺ㋙こ])',
                        'サ' => '([サｻ㋚さ🈂])',
                        'シ' => '([シｼ㋛し])',
                        'ス' => '([スｽ㋜す])',
                        'セ' => '([セｾ㋝せ])',
                        'ソ' => '([ソｿ㋞そ])',
                        'タ' => '([タﾀ㋟た])',
                        'チ' => '([チﾁ㋠ち])',
                        'ツ' => '([ツﾂ㋡つッｯっ])',
                        'テ' => '([テﾃ㋢て])',
                        'ト' => '([トﾄ㋣と])',
                        'ナ' => '([ナﾅ㋤な])',
                        'ニ' => '([ニﾆ㊁㋥に🈔])',
                        'ヌ' => '([ヌﾇ㋦ぬ])',
                        'ネ' => '([ネﾈ㋧ね])',
                        'ノ' => '([ノﾉ㋨の])',
                        'ハ' => '([ハﾊ㋩は])',
                        'ヒ' => '([ヒﾋ㋪ひ])',
                        'フ' => '([フﾌ㋫ふ])',
                        'ヘ' => '([ヘﾍ㋬へ])',
                        'ホ' => '([ホﾎ㋭ほ])',
                        'マ' => '([マﾏ㋮ま])',
                        'ミ' => '([ミﾐ㋯み])',
                        'ム' => '([ムﾑ㋰む])',
                        'メ' => '([メﾒ㋱め])',
                        'モ' => '([モﾓ㋲も])',
                        'ヤ' => '([ヤﾔ㋳やャｬゃ])',
                        'ユ' => '([ユﾕ㋴ゆュｭゅ])',
                        'ヨ' => '([ヨﾖ㋵よョｮょ])',
                        'ラ' => '([ラﾗ㋶ら])',
                        'リ' => '([リﾘ㋷り])',
                        'ル' => '([ルﾙ㋸る])',
                        'レ' => '([レﾚ㋹れ])',
                        'ロ' => '([ロﾛ㋺ろ])',
                        'ワ' => '([ワﾜ㋻わヮゎ])',
                        'ヲ' => '([ヲｦ㋾を])',
                        'ン' => '([ンﾝん])',
                        'ガ' => '([ガが]|[カヵｶか][゛ﾞ])',
                        'ギ' => '([ギぎ]|[キｷき][゛ﾞ])',
                        'グ' => '([グぐ]|[クｸく][゛ﾞ])',
                        'ゲ' => '([ゲげ]|[ケヶｹけ][゛ﾞ])',
                        'ゴ' => '([ゴご]|[コｺこ][゛ﾞ])',
                        'ザ' => '([ザざ]|[サｻさ][゛ﾞ])',
                        'ジ' => '([ジじ]|[シｼし][゛ﾞ])',
                        'ズ' => '([ズず]|[スｽす][゛ﾞ])',
                        'ゼ' => '([ゼぜ]|[セｾせ][゛ﾞ])',
                        'ゾ' => '([ゾぞ]|[ソｿそ][゛ﾞ])',
                        'ダ' => '([ダだ]|[タﾀた][゛ﾞ])',
                        'ヂ' => '([ヂぢ]|[チﾁち][゛ﾞ])',
                        'ヅ' => '([ヅづ]|[ツッﾂつっ][゛ﾞ])',
                        'デ' => '([デで]|[テﾃて][゛ﾞ])',
                        'ド' => '([ドど]|[トﾄと][゛ﾞ])',
                        'バ' => '([バば]|[ハﾊは][゛ﾞ])',
                        'ビ' => '([ビび]|[ヒﾋひ][゛ﾞ])',
                        'ブ' => '([ブぶ]|[フﾌふ][゛ﾞ])',
                        'ベ' => '([ベべ]|[ヘﾍへ][゛ﾞ])',
                        'ボ' => '([ボぼ]|[ホﾎほ][゜ﾟ])',
                        'パ' => '([パぱ]|[ハﾊは][゜ﾟ])',
                        'ピ' => '([ピぴ]|[ヒﾋひ][゜ﾟ])',
                        'プ' => '([プぷ]|[フﾌふ][゜ﾟ])',
                        'ペ' => '([ペぺ]|[ヘﾍへ][゜ﾟ])',
                        'ポ' => '([ポぽ]|[ホﾎほ][゜ﾟ])',
                        'ヴ' => '(ヴ|[ウゥｳうぅ][゛ﾞ])',
                        'ァ' => '([アｱ㋐あァｧぁ])',
                        'ィ' => '([イｲ㋑㋼いィｨぃヰゐ])',
                        'ゥ' => '([ウｳ㋒うゥｩぅヱゑ])',
                        'ェ' => '([エｴ㋓㋽えェｪぇ])',
                        'ォ' => '([オｵ㋔おォｫぉ])',
                        'ヵ' => '([カｶ㋕かヵゕ])',
                        'ヶ' => '([ケｹ㋘けヶ])',
                        'ッ' => '([ツﾂ㋡つッｯっ])',
                        'ャ' => '([ヤﾔ㋳やャｬゃ])',
                        'ュ' => '([ユﾕ㋴ゆュｭゅ])',
                        'ョ' => '([ヨﾖ㋵よョｮょ])',
                        'ヮ' => '([ワﾜ㋻わヮゎ])',
                        '゛' => '([゛ﾞ])',
                        '゜' => '([゜ﾟ])',
                        'ー' => '([ー-])',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get the default translator for this validations.
     *
     * @return Translator
     */
    public function translator() : Translator
    {
        return new Translator(new FileLoader(static::config('resources.i18n')));
    }

    // ====================================================
    // Built-in Validation Methods
    // ====================================================

    /**
     * Satisfy validation/condition
     *
     * @param Context $c
     * @param \Closure $test
     * @return boolean
     */
    public function validationSatisfy(Context $c, \Closure $test) : bool
    {
        return $test($c);
    }

    /**
     * Required Validation
     *
     * @param Context $c
     * @return boolean
     */
    public function validationRequired(Context $c) : bool
    {
        return $c->blank() ? $c->appendError('validation.Required') : true ;
    }

    /**
     * Required If Validation
     *
     * @param Context $c
     * @param string $other field name
     * @param mixed $value value or array or :field_name
     * @return boolean
     */
    public function validationRequiredIf(Context $c, string $other, $value) : bool
    {
        return !$c->blank() ? true : $this->handleIf($c, $other, $value, function ($c, $other, $value, $label) {
            return $c->appendError('validation.RequiredIf', ['other' => $c->label($other), 'value' => $label], Arrays::count($value));
        });
    }
    
    /**
     * Required Unless Validation
     *
     * @param Context $c
     * @param string $other field name
     * @param mixed $value value or array or :field_name
     * @return boolean
     */
    public function validationRequiredUnless(Context $c, string $other, $value) : bool
    {
        return !$c->blank() ? true : $this->handleUnless($c, $other, $value, function ($c, $other, $value, $label) {
            return $c->appendError('validation.RequiredUnless', ['other' => $c->label($other), 'value' => $label], Arrays::count($value));
        });
    }
    
    /**
     * Handle If validate precondition
     *
     * @param Context $c
     * @param string $other
     * @param string|array $value value or array or :field_name
     * @param callable $callback function(Context $c, string $other, $value, string $label) { ... }
     * @return boolean
     */
    public function handleIf(Context $c, string $other, $value, callable $callback) : bool
    {
        [$value, $label] = $c->resolve($value);
        if (in_array($c->value($other), is_null($value) ? [null] : (array)$value)) {
            return $callback($c, $other, $value, $label);
        }
        return true;
    }

    /**
     * Handle Unless validate precondition
     *
     * @param Context $c
     * @param string $other
     * @param string|array $value value or array or :field_name
     * @param callable $callback function(Context $c, string $other, $value, string $label) { ... }
     * @return boolean
     */
    public function handleUnless(Context $c, string $other, $value, callable $callback) : bool
    {
        [$value, $label] = $c->resolve($value);
        if (!in_array($c->value($other), is_null($value) ? [null] : (array)$value)) {
            return $callback($c, $other, $value, $label);
        }
        return true;
    }
    
    /**
     * Required With Validation
     *
     * @param Context $c
     * @param string|array $other field names
     * @param int|null $at_least (default: null)
     * @return boolean
     */
    public function validationRequiredWith(Context $c, $other, ?int $at_least = null) : bool
    {
        return !$c->blank() ? true : $this->handleWith($c, $other, $at_least, function ($c, $other, $at_least, $max, $inputed) {
            return $c->appendError(
                'validation.RequiredWith',
                ['other' => $c->labels($other), 'at_least' => $at_least],
                Arrays::count($other) === 1 ? 'one' : ($at_least < $max ? 'some' : 'all')
            );
        });
    }

    /**
     * Required Without Validation
     *
     * @param Context $c
     * @param string|array $other field names
     * @param int|null $at_least (default: null)
     * @return boolean
     */
    public function validationRequiredWithout(Context $c, $other, ?int $at_least = null) : bool
    {
        return !$c->blank() ? true : $this->handleWithout($c, $other, $at_least, function ($c, $other, $at_least, $max, $not_inputed) {
            return $c->appendError(
                'validation.RequiredWithout',
                ['other' => $c->labels($other), 'at_least' => $at_least],
                Arrays::count($other) === 1 ? 'one' : ($at_least < $max ? 'some' : 'all')
            );
        });
    }
    
    /**
     * Handle With validate precondition
     *
     * @param Context $c
     * @param string|array $other
     * @param integer|null $at_least
     * @param callable $callback function(Context $c, $other, ?int $at_least, int $max, int $inputed){ ... }
     * @return boolean
     */
    public function handleWith(Context $c, $other, ?int $at_least, callable $callback) : bool
    {
        $other    = (array)$other;
        $max      = count($other);
        $at_least = $at_least ?? $max;
        $inputed  = 0;
        foreach ($other as $field) {
            $inputed += $c->blank($field) ? 0 : 1 ;
        }
        if ($inputed >= $at_least) {
            return $callback($c, $other, $at_least, $max, $inputed);
        }
        return true;
    }

    /**
     * Handle Without validate precondition
     *
     * @param Context $c
     * @param string|array $other
     * @param integer|null $at_least
     * @param callable $callback function(Context $c, $other, ?int $at_least, int $max, int $not_inputed){ ... }
     * @return boolean
     */
    public function handleWithout(Context $c, $other, ?int $at_least, callable $callback) : bool
    {
        $other       = (array)$other;
        $max         = count($other);
        $at_least    = $at_least ?? $max;
        $not_inputed = 0;
        foreach ($other as $field) {
            $not_inputed += $c->blank($field) ? 1 : 0 ;
        }
        if ($not_inputed >= $at_least) {
            return $callback($c, $other, $at_least, $max, $not_inputed);
        }
        return true;
    }
    
    /**
     * Blank If Validation
     *
     * @param Context $c
     * @param string $other field name
     * @param mixed $value value or array or :field_name
     * @return boolean
     */
    public function validationBlankIf(Context $c, string $other, $value) : bool
    {
        return $c->blank() ? true : $this->handleIf($c, $other, $value, function ($c, $other, $value, $label) {
            return $c->appendError('validation.BlankIf', ['other' => $c->label($other), 'value' => $label], Arrays::count($value));
        });
    }
    
    /**
     * Blank Unless Validation
     *
     * @param Context $c
     * @param string $other field name
     * @param mixed $value value or array or :field_name
     * @return boolean
     */
    public function validationBlankUnless(Context $c, string $other, $value) : bool
    {
        return $c->blank() ? true : $this->handleUnless($c, $other, $value, function ($c, $other, $value, $label) {
            return $c->appendError('validation.BlankUnless', ['other' => $c->label($other), 'value' => $label], Arrays::count($value));
        });
    }

    /**
     * Blank With Validation
     *
     * @param Context $c
     * @param string|array $other field names
     * @param int|null $at_least (default: null)
     * @return boolean
     */
    public function validationBlankWith(Context $c, $other, ?int $at_least = null) : bool
    {
        return $c->blank() ? true : $this->handleWith($c, $other, $at_least, function ($c, $other, $at_least, $max, $inputed) {
            return $c->appendError(
                'validation.BlankWith',
                ['other' => $c->labels($other), 'at_least' => $at_least],
                Arrays::count($other) === 1 ? 'one' : ($at_least < $max ? 'some' : 'all')
            );
        });
    }

    /**
     * Blank Without Validation
     *
     * @param Context $c
     * @param string|array $other field names
     * @param int|null $at_least (default: null)
     * @return boolean
     */
    public function validationBlankWithout(Context $c, $other, ?int $at_least = null) : bool
    {
        return $c->blank() ? true : $this->handleWithout($c, $other, $at_least, function ($c, $other, $at_least, $max, $not_inputed) {
            return $c->appendError(
                'validation.BlankWithout',
                ['other' => $c->labels($other), 'at_least' => $at_least],
                Arrays::count($other) === 1 ? 'one' : ($at_least < $max ? 'some' : 'all')
            );
        });
    }

    /**
     * Same As Validation
     *
     * @param Context $c
     * @param mixed $value
     * @return boolean
     */
    public function validationSameAs(Context $c, $value) : bool
    {
        if ($c->blank()) {
            return true;
        }
        [$value, $label] = $c->resolve($value);
        return $c->value == $value ? true : $c->appendError('validation.SameAs', ['value' => $label]);
    }

    /**
     * Not Same As Validation
     *
     * @param Context $c
     * @param mixed $value
     * @return boolean
     */
    public function validationNotSameAs(Context $c, $value) : bool
    {
        if ($c->blank()) {
            return true;
        }
        [$value, $label] = $c->resolve($value);
        return $c->value != $value ? true : $c->appendError('validation.NotSameAs', ['value' => $label]);
    }

    /**
     * Regex Validation
     *
     * @param Context $c
     * @param string $pattern
     * @param string $selector (default: null)
     * @return boolean
     */
    public function validationRegex(Context $c, string $pattern, string $selector = null) : bool
    {
        return $this->handleRegex($c, Kind::OTHER(), $pattern, 'validation.Regex', ['pattern' => $pattern], $selector);
    }

    /**
     * Handle Listable Value Type Validation
     * If you use this handler then you have to define @List message key too.
     *
     * @param Context $c
     * @param Kind $kind
     * @param callable $test function($value) { ... }
     * @param string $messsage_key
     * @param array $replacement (default: [])
     * @param callable $selector function($value) { ... } (default: null)
     * @return boolean
     */
    public function handleListableValue(Context $c, Kind $kind, callable $test, string $messsage_key, array $replacement = [], callable $selector = null) : bool
    {
        if ($c->blank()) {
            return true;
        }
        $valid         = true;
        $error_indices = $c->extra('error_indices') ?? [];
        foreach ((array)$c->value as $i => $value) {
            if (!$c->isQuiet() && !$kind->equals(Kind::OTHER()) && $error_indices[$i] ?? false) {
                continue;
            }
            if (!$test($value)) {
                $replacement['nth']   = $c->ordinalize($i + 1);
                $replacement['value'] = $value;
                $valid                = $c->appendError($messsage_key.(is_array($c->value) ? '@List' : ''), $replacement, $selector ? $selector($value) : null);
                if ($kind->equals(Kind::TYPE_CONSISTENCY_CHECK())) {
                    $error_indices[$i] = true;
                }
            }
        }
        $c->setExtra('error_indices', $error_indices);
        return $valid;
    }

    /**
     * Handle Regex Type Validation
     * If you use this handler then you have to define @List message key too.
     *
     * @param Context $c
     * @param Kind $kind
     * @param string $pattern
     * @param string $messsage_key
     * @param array $replacement (default: [])
     * @param int|string $selector (default: null)
     * @return boolean
     */
    public function handleRegex(Context $c, Kind $kind, string $pattern, string $messsage_key, array $replacement = [], $selector = null) : bool
    {
        return $this->handleListableValue(
            $c,
            $kind,
            function ($value) use ($pattern) {
                return preg_match($pattern, $value);
            },
            $messsage_key,
            $replacement,
            function ($value) use ($selector) {
                return $selector;
            }
        );
    }
    
    /**
     * Not Regex Validation
     *
     * @param Context $c
     * @param string $pattern
     * @param string $selector (default: null)
     * @return boolean
     */
    public function validationNotRegex(Context $c, string $pattern, string $selector = null) : bool
    {
        return $this->handleNotRegex($c, Kind::OTHER(), $pattern, 'validation.NotRegex', ['pattern' => $pattern], $selector);
    }

    /**
     * Handle Not Regex type validation
     * If you use this handler then you have to define @List message key too.
     *
     * @param Context $c
     * @param Kind $kind
     * @param string $pattern
     * @param string $messsage_key
     * @param array $replacement (default: [])
     * @param int|string $selector (default: null)
     * @return boolean
     */
    public function handleNotRegex(Context $c, Kind $kind, string $pattern, string $messsage_key, array $replacement = [], $selector = null) : bool
    {
        return $this->handleListableValue(
            $c,
            $kind,
            function ($value) use ($pattern) {
                return !preg_match($pattern, $value);
            },
            $messsage_key,
            $replacement,
            function ($value) use ($selector) {
                return $selector;
            }
        );
    }
    
    /**
     * Max Length Validation
     *
     * @param Context $c
     * @param integer $max
     * @return boolean
     */
    public function validationMaxLength(Context $c, int $max) : bool
    {
        return $this->handleListableValue(
            $c,
            Kind::OTHER(),
            function ($value) use ($max) {
                return mb_strlen($value) <= $max;
            },
            'validation.MaxLength',
            ['max' => $max]
        );
    }

    /**
     * Min Length Validation
     *
     * @param Context $c
     * @param integer $min
     * @return boolean
     */
    public function validationMinLength(Context $c, int $min) : bool
    {
        return $this->handleListableValue(
            $c,
            Kind::OTHER(),
            function ($value) use ($min) {
                return mb_strlen($value) >= $min;
            },
            'validation.MinLength',
            ['min' => $min]
        );
    }

    /**
     * Length Validation
     *
     * @param Context $c
     * @param integer $length
     * @return boolean
     */
    public function validationLength(Context $c, int $length) : bool
    {
        return $this->handleListableValue(
            $c,
            Kind::OTHER(),
            function ($value) use ($length) {
                return mb_strlen($value) === $length;
            },
            'validation.Length',
            ['length' => $length]
        );
    }

    /**
     * Number Validation
     *
     * @param Context $c
     * @return boolean
     */
    public function validationNumber(Context $c) : bool
    {
        return $this->handleRegex($c, Kind::TYPE_CONSISTENCY_CHECK(), "/^[+-]?[0-9]*[\.]?[0-9]+$/u", 'validation.Number');
    }

    /**
     * Integer Validation
     *
     * @param Context $c
     * @return boolean
     */
    public function validationInteger(Context $c) : bool
    {
        return $this->handleRegex($c, Kind::TYPE_CONSISTENCY_CHECK(), "/^[+-]?[0-9]+$/u", 'validation.Integer');
    }

    /**
     * Float Validation
     *
     * @param Context $c
     * @param int $decimal
     * @return boolean
     */
    public function validationFloat(Context $c, int $decimal) : bool
    {
        return $this->handleRegex($c, Kind::TYPE_CONSISTENCY_CHECK(), "/^[+-]?[0-9]+([\.][0-9]{0,{$decimal}})?$/u", 'validation.Float', ['decimal' => $decimal]);
    }

    /**
     * Max Number Validation
     *
     * @param Context $c
     * @param int|float|string $max
     * @param int $decimal (default: 0)
     * @return boolean
     */
    public function validationMaxNumber(Context $c, $max, int $decimal = 0) : bool
    {
        if ($c->blank()) {
            return true;
        }
        
        $valid  = $decimal === 0 ? $this->validationInteger($c) : $this->validationFloat($c, $decimal) ;
        $valid &= $this->handleListableValue(
            $c,
            Kind::TYPE_DEPENDENT_CHECK(),
            function ($value) use ($max, $decimal) {
                return bccomp((string)$value, (string)$max, $decimal) !== 1;
            },
            'validation.MaxNumber',
            ['max' => $max, 'decimal' => $decimal]
        );
        return $valid;
    }

    /**
     * Min Number Validation
     *
     * @param Context $c
     * @param int|float|string $min
     * @param int $decimal (default: 0)
     * @return boolean
     */
    public function validationMinNumber(Context $c, $min, int $decimal = 0) : bool
    {
        if ($c->blank()) {
            return true;
        }
        
        $valid  = $decimal === 0 ? $this->validationInteger($c) : $this->validationFloat($c, $decimal) ;
        $valid &= $this->handleListableValue(
            $c,
            Kind::TYPE_DEPENDENT_CHECK(),
            function ($value) use ($min, $decimal) {
                return bccomp((string)$min, (string)$value, $decimal) !== 1;
            },
            'validation.MinNumber',
            ['min' => $min, 'decimal' => $decimal]
        );
        return $valid;
    }

    /**
     * Email Validation
     *
     * @param Context $c
     * @param bool $strict (default: true)
     * @return boolean
     */
    public function validationEmail(Context $c, bool $strict = true) : bool
    {
        if ($strict) {
            return $this->handleListableValue(
                $c,
                Kind::TYPE_CONSISTENCY_CHECK(),
                function ($value) {
                    return filter_var($value, FILTER_VALIDATE_EMAIL);
                },
                'validation.Email'
            );
        }

        return $this->handleRegex($c, Kind::TYPE_CONSISTENCY_CHECK(), "/[A-Z0-9a-z._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,64}/", 'validation.Email');
    }

    /**
     * Url Validation
     *
     * @param Context $c
     * @param bool $dns_check (default: false)
     * @return boolean
     */
    public function validationUrl(Context $c, bool $dns_check = false) : bool
    {
        if ($c->blank()) {
            return true;
        }
        
        /*
         * This pattern is derived from Symfony\Component\Validator\Constraints\UrlValidator (2.7.4).
         *
         * (c) Fabien Potencier <fabien@symfony.com> http://symfony.com
         */
        $pattern = '~^
            ((aaa|aaas|about|acap|acct|acr|adiumxtra|afp|afs|aim|apt|attachment|aw|barion|beshare|bitcoin|blob|bolo|callto|cap|chrome|chrome-extension|cid|coap|coaps|com-eventbrite-attendee|content|crid|cvs|data|dav|dict|dlna-playcontainer|dlna-playsingle|dns|dntp|dtn|dvb|ed2k|example|facetime|fax|feed|feedready|file|filesystem|finger|fish|ftp|geo|gg|git|gizmoproject|go|gopher|gtalk|h323|ham|hcp|http|https|iax|icap|icon|im|imap|info|iotdisco|ipn|ipp|ipps|irc|irc6|ircs|iris|iris.beep|iris.lwz|iris.xpc|iris.xpcs|itms|jabber|jar|jms|keyparc|lastfm|ldap|ldaps|magnet|mailserver|mailto|maps|market|message|mid|mms|modem|ms-help|ms-settings|ms-settings-airplanemode|ms-settings-bluetooth|ms-settings-camera|ms-settings-cellular|ms-settings-cloudstorage|ms-settings-emailandaccounts|ms-settings-language|ms-settings-location|ms-settings-lock|ms-settings-nfctransactions|ms-settings-notifications|ms-settings-power|ms-settings-privacy|ms-settings-proximity|ms-settings-screenrotation|ms-settings-wifi|ms-settings-workplace|msnim|msrp|msrps|mtqp|mumble|mupdate|mvn|news|nfs|ni|nih|nntp|notes|oid|opaquelocktoken|pack|palm|paparazzi|pkcs11|platform|pop|pres|prospero|proxy|psyc|query|redis|rediss|reload|res|resource|rmi|rsync|rtmfp|rtmp|rtsp|rtsps|rtspu|secondlife|s3|service|session|sftp|sgn|shttp|sieve|sip|sips|skype|smb|sms|smtp|snews|snmp|soap.beep|soap.beeps|soldat|spotify|ssh|steam|stun|stuns|submit|svn|tag|teamspeak|tel|teliaeid|telnet|tftp|things|thismessage|tip|tn3270|turn|turns|tv|udp|unreal|urn|ut2004|vemmi|ventrilo|videotex|view-source|wais|webcal|ws|wss|wtai|wyciwyg|xcon|xcon-userid|xfire|xmlrpc\.beep|xmlrpc.beeps|xmpp|xri|ymsgr|z39\.50|z39\.50r|z39\.50s))://                                 # protocol
            (([\pL\pN-]+:)?([\pL\pN-]+)@)?          # basic auth
            (
                ([\pL\pN\pS\-\.])+(\.?([\pL]|xn\-\-[\pL\pN-]+)+\.?) # a domain name
                    |                                              # or
                \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}                 # an IP address
                    |                                              # or
                \[
                    (?:(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){6})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:::(?:(?:(?:[0-9a-f]{1,4})):){5})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){4})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,1}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){3})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,2}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){2})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,3}(?:(?:[0-9a-f]{1,4})))?::(?:(?:[0-9a-f]{1,4})):)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,4}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,5}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,6}(?:(?:[0-9a-f]{1,4})))?::))))
                \]  # an IPv6 address
            )
            (:[0-9]+)?                              # a port (optional)
            (/?|/\S+|\?\S*|\#\S*)                   # a /, nothing, a / with something, a query or a fragment
        $~ixu';
        $valid = $this->handleRegex($c, Kind::TYPE_CONSISTENCY_CHECK(), $pattern, 'validation.Url');
        if ($dns_check) {
            $host_state = [];
            $valid &= $this->handleListableValue(
                $c,
                Kind::TYPE_DEPENDENT_CHECK(),
                function ($value) use (&$host_state) {
                    $host = parse_url($value, PHP_URL_HOST);
                    if (isset($host_state[$host])) {
                        return $host_state[$host];
                    }
                    $active = $host ? count(System::dns_get_record($host, DNS_A | DNS_AAAA)) > 0 : false ;
                    $host_state[$host] = $active;
                    return $active;
                },
                'validation.Url',
                [],
                function ($value) {
                    return 'nonactive';
                }
            );
        }
        return $valid;
    }

    /**
     * IPv4 Validation
     *
     * @param Context $c
     * @param bool $delimiter (default: null)
     * @return boolean
     */
    public function validationIpv4(Context $c, string $delimiter = null) : bool
    {
        if (!is_null($delimiter) && is_string($c->value)) {
            $splited = [];
            foreach (explode($delimiter, $c->value) as $value) {
                $value = trim($value);
                if (!Utils::isBlank($value)) {
                    $splited[] = $value;
                }
            }
            $c->value = $splited;
        }
        return $this->handleRegex($c, Kind::TYPE_CONSISTENCY_CHECK(), "/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/([1-9]|[1-2][0-9]|3[0-2]))?$/u", 'validation.Ipv4');
    }

    /**
     * Digit Validation
     *
     * @param Context $c
     * @return boolean
     */
    public function validationDigit(Context $c) : bool
    {
        return $this->handleRegex($c, Kind::OTHER(), "/^[0-9]+$/u", 'validation.Digit');
    }

    /**
     * Alpha Validation
     *
     * @param Context $c
     * @return boolean
     */
    public function validationAlpha(Context $c) : bool
    {
        return $this->handleRegex($c, Kind::OTHER(), "/^[a-zA-Z]+$/u", 'validation.Alpha');
    }

    /**
     * Alpha Digit Validation
     *
     * @param Context $c
     * @return boolean
     */
    public function validationAlphaDigit(Context $c) : bool
    {
        return $this->handleRegex($c, Kind::OTHER(), "/^[a-zA-Z0-9]+$/u", 'validation.AlphaDigit');
    }

    /**
     * Alpha Digit Mark Validation
     *
     * @param Context $c
     * @param string $mark (default: '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~ ')
     * @return boolean
     */
    public function validationAlphaDigitMark(Context $c, string $mark = '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~ ') : bool
    {
        return $this->handleRegex($c, Kind::OTHER(), "/^[a-zA-Z0-9".preg_quote($mark, '/')."]+$/u", 'validation.AlphaDigitMark', ['mark' => $mark]);
    }

    /**
     * Hiragana Validation
     *
     * @param Context $c
     * @param string $extra (default: '')
     * @return boolean
     */
    public function validationHiragana(Context $c, string $extra = '') : bool
    {
        return $this->handleRegex($c, Kind::OTHER(), "/^[\p{Hiragana}ー".preg_quote($extra, '/')."]+$/u", 'validation.Hiragana', ['extra' => $extra]);
    }

    /**
     * Kana Validation
     *
     * @param Context $c
     * @param string $extra (default: '')
     * @return boolean
     */
    public function validationKana(Context $c, string $extra = '') : bool
    {
        return $this->handleRegex($c, Kind::OTHER(), "/^[ァ-ヾ".preg_quote($extra, '/')."]+$/u", 'validation.Kana', ['extra' => $extra]);
    }

    /**
     * Dependence Char Validation
     *
     * @param Context $c
     * @param string $encode (default: depend on configure)
     * @return boolean
     */
    public function validationDependenceChar(Context $c, string $encode = null) : bool
    {
        $encode      = $encode ?? static::config('default.DependenceChar.encode');
        $dependences = [];
        return $this->handleListableValue(
            $c,
            Kind::OTHER(),
            function ($value) use ($encode, &$dependences) {
                $org         = $value;
                $conv        = mb_convert_encoding(mb_convert_encoding($value, $encode, 'UTF-8'), 'UTF-8', $encode);
                $dependences = [];
                if (strlen($org) != strlen($conv)) {
                    $dependences = array_diff(Strings::toCharArray($org), Strings::toCharArray($conv));
                    return empty($dependences);
                }
                return true;
            },
            'validation.DependenceChar',
            ['encode' => $encode, 'dependences' => &$dependences]
        );
    }

    /**
     * Ng Word Validation
     *
     * @param Context $c
     * @param string|array $ng_words
     * @param string|null $word_split_pattern (default: depend on configure)
     * @param string|null $delimiter_pattern (default: depend on configure)
     * @param string|null $omission_pattern (default: depend on configure)
     * @param int|null $omission_length (default: depend on configure)
     * @param float|null $omission_ratio (default: depend on configure)
     * @return boolean
     */
    public function validationNgWord(Context $c, $ng_words, ?string $word_split_pattern = null, ?string $delimiter_pattern = null, ?string $omission_pattern = null, ?int $omission_length = null, ?float $omission_ratio = null) : bool
    {
        $word_split_pattern = $word_split_pattern ?? static::config('default.NgWord.word_split_pattern') ;
        $delimiter_pattern  = $delimiter_pattern ?? static::config('default.NgWord.delimiter_pattern') ;
        $omission_pattern   = $omission_pattern ?? static::config('default.NgWord.omission_pattern') ;
        $omission_length    = $omission_length ?? static::config('default.NgWord.omission_length') ;
        $omission_ratio     = $omission_ratio ?? static::config('default.NgWord.omission_ratio') ;
        $ambiguous_patterns = static::config('default.NgWord.ambiguous_patterns') ;

        if (!is_array($ng_words)) {
            $ng_words = file($ng_words, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        $hit_ng_word = null;
        return $this->handleListableValue(
            $c,
            Kind::OTHER(),
            function ($text) use ($ng_words, $word_split_pattern, $delimiter_pattern, $omission_pattern, $omission_length, $omission_ratio, $ambiguous_patterns, &$hit_ng_word) {
                $length = mb_strlen($text);
                foreach ($ng_words as $ng_word) {
                    if (mb_strlen(trim($ng_word, '^$')) > $length) {
                        continue;
                    }

                    $word_length = mb_strlen($ng_word);
                    $tolerance   = $word_length * $omission_ratio;
                    $regex       = $this->ngWordToMatcher($ng_word, $word_split_pattern, $delimiter_pattern, $omission_pattern, $omission_length, $ambiguous_patterns);
                    $matches     = [];
                    $offset      = 0;
                    while (preg_match($regex, $text, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                        $hit_word = empty($word_split_pattern) ? $matches[0][0] : preg_replace("/^{$word_split_pattern}|{$word_split_pattern}$/u", '', $matches[0][0]) ;
                        $offset   = $matches[0][1] + 1;
                        $distance = 0;

                        for ($i = 0 ; $i < $word_length ; $i++) {
                            $distance += ($matches["o{$i}"][0] ?: false) ? 1 : 0 ;
                        }
                        if ($distance <= $tolerance) {
                            $hit_ng_word = $hit_word;
                            return false;
                        }
                    }
                }
                return true;
            },
            'validation.NgWord',
            ['ng_word' => &$hit_ng_word]
        );
    }

    /**
     * Create a regex matcher from given ng word.
     *
     * @param string $ng_word
     * @param string|null $word_split_pattern
     * @param string $delimiter_pattern
     * @param string $omission_pattern
     * @param array $ambiguous_patterns
     * @return string
     */
    private function ngWordToMatcher(string $ng_word, string $word_split_pattern, string $delimiter_pattern, string $omission_pattern, int $omission_length, array $ambiguous_patterns) : string
    {
        $regex          = '';
        $ng_word_length = mb_strlen($ng_word);
        foreach (Strings::toCharArray($ng_word) as $i => $letter) {
            $ambiguous_pattern = $ambiguous_patterns[$letter] ?? preg_quote($letter, '/') ;
            switch ($ambiguous_pattern) {
                case '^':
                    $regex .= $ambiguous_pattern.$delimiter_pattern.'*';
                    continue;
                case '$':
                    $regex .= $ambiguous_pattern;
                    continue;
                case $ng_word_length < $omission_length:
                    $regex .= "{$ambiguous_pattern}{$delimiter_pattern}*";
                    continue;
                default:
                    $regex .= "(?:{$ambiguous_pattern}|(?<o{$i}>{$omission_pattern})){$delimiter_pattern}*";
            }
        }
        return empty($word_split_pattern) ? "/{$regex}/u" : "/(?:{$word_split_pattern}|^){$regex}(?:{$word_split_pattern}|$)/u";
    }

    /**
     * Contains Validation
     *
     * @param Context $c
     * @param array $list
     * @return boolean
     */
    public function validationContains(Context $c, array $list) : bool
    {
        return $this->handleListableValue(
            $c,
            Kind::TYPE_CONSISTENCY_CHECK(),
            function ($value) use ($list) {
                return in_array($value, $list);
            },
            'validation.Contains',
            ['list' => $list]
        );
    }

    /**
     * Min Count Validation
     *
     * @param Context $c
     * @param int $min
     * @return boolean
     */
    public function validationMinCount(Context $c, int $min) : bool
    {
        $item_count = $c->blank() ? 0 : Arrays::count($c->value) ;
        return $item_count < $min ? $c->appendError('validation.MinCount', ['item_count' => $item_count, 'min' => $min], $min) : true;
    }

    /**
     * Max Count Validation
     *
     * @param Context $c
     * @param int $max
     * @return boolean
     */
    public function validationMaxCount(Context $c, int $max) : bool
    {
        $item_count = $c->blank() ? 0 : Arrays::count($c->value) ;
        return $item_count > $max ? $c->appendError('validation.MaxCount', ['item_count' => $item_count, 'max' => $max], $max) : true;
    }

    /**
     * Count Validation
     *
     * @param Context $c
     * @param int $count
     * @return boolean
     */
    public function validationCount(Context $c, int $count) : bool
    {
        $item_count = $c->blank() ? 0 : Arrays::count($c->value) ;
        return $item_count !== $count ? $c->appendError('validation.Count', ['item_count' => $item_count, 'count' => $count], $count) : true;
    }

    /**
     * Unique Validation
     *
     * @param Context $c
     * @return boolean
     */
    public function validationUnique(Context $c) : bool
    {
        if ($c->blank()) {
            return true;
        }
        $duplicate = Arrays::duplicate((array)$c->value);
        return empty($duplicate) ? true : $c->appendError('validation.Unique', ['duplicate' => $duplicate], count($duplicate)) ;
    }

    /**
     * Datetime Validation
     *
     * @param Context $c
     * @param string|array $format
     * @return boolean
     */
    public function validationDatetime(Context $c, $format = []) : bool
    {
        return $this->handleListableValue(
            $c,
            Kind::TYPE_CONSISTENCY_CHECK(),
            function ($value) use ($format) {
                return !is_null(DateTime::createDateTime($value, $format));
            },
            'validation.Datetime'
        );
    }

    /**
     * Future Than Validation
     *
     * @param Context $c
     * @param string|\DateTimeInterface $at_time
     * @param string|array $format
     * @return boolean
     */
    public function validationFutureThan(Context $c, $at_time, $format = []) : bool
    {
        return $this->handleDatetime(
            $c,
            $at_time,
            $format,
            function (DateTime $value, DateTime $at_time) {
                return $value > $at_time;
            },
            'validation.FutureThan'
        );
    }

    /**
     * Handle Datetime validation.
     * If you use this handler then you have to define @List message key too.
     *
     * @param Context $c
     * @param string|\DateTimeInterface $at_time
     * @param string|array $format
     * @param callable $test function(DateTime $value, DateTime at_time){ ... }
     * @param string $messsage_key
     * @return boolean
     */
    public function handleDatetime(Context $c, $at_time, $format = [], callable $test, string $messsage_key, array $replacement = [], callable $selector = null) : bool
    {
        [$at_time, $at_time_label] = $c->resolve($at_time);
        $replacement['at_time']    = $at_time_label;
        $valid                     = $this->validationDatetime($c, $format);
        $valid &= $this->handleListableValue(
            $c,
            Kind::TYPE_DEPENDENT_CHECK(),
            function ($value) use ($at_time, $format, $test) {
                try {
                    $at_time = DateTime::createDateTime($at_time, $format) ?? new DateTime($at_time);
                } catch (\Exception $e) {
                    return true;
                }
                return $test(DateTime::createDateTime($value, $format), $at_time);
            },
            $messsage_key,
            $replacement,
            $selector
        );
        return $valid;
    }

    // ====================================================
    // Built-in Condition Methods
    // ====================================================

    /**
     * If condition
     *
     * @param Context $c
     * @param string $other
     * @param mixed $value value or array :field_name
     * @return boolean
     */
    public function validationIf(Context $c, string $other, $value) : bool
    {
        [$value, ] = $c->resolve($value);
        return in_array($c->value($other), is_null($value) ? [null] : (array)$value);
    }

    /**
     * Unless condition
     *
     * @param Context $c
     * @param string $other
     * @param mixed $value value or array or @field_name
     * @return boolean
     */
    public function validationUnless(Context $c, string $other, $value) : bool
    {
        [$value, ] = $c->resolve($value);
        return !in_array($c->value($other), is_null($value) ? [null] : (array)$value);
    }
}
