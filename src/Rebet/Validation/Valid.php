<?php
namespace Rebet\Validation;

/**
 * Valid Class
 *
 * This class is constants list of built-in validation names.
 * Some definitions (ex REQUIRED/DATE_TIME) includes '!' (validation stop if failed) option defaultly.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Valid
{
    /**
     * If Condition.
     * It checks the value of other field is [given value/in given array/value of given field].
     * This validation does not output an error message even test is failed.
     * This behavior is useful for describing validation execution conditions using 'then' or/and 'else'.
     *
     * ex)
     *   - ['CU', Valid::IF, 'other', value, 'then' => [...], 'else' => [...]]
     *   - ['CU', Valid::IF, 'other', [value1, value2, ...], 'then' => [...], 'else' => [...]]
     *   - ['CU', Valid::IF, 'other', ':field', (snip)]
     */
    const IF = 'If:';

    /**
     * Unless Condition.
     * It checks the value of other field is not [given value/in given array/value of given field].
     * This validation does not output an error message even test is failed.
     * This behavior is useful for describing validation execution conditions using 'then' or/and 'else'.
     *
     * ex)
     *   - ['CU', Valid::UNLESS, 'other', value, 'then' => [...], 'else' => [...]]
     *   - ['CU', Valid::UNLESS, 'other', [value1, value2, ...], 'then' => [...], 'else' => [...]]
     *   - ['CU', Valid::UNLESS, 'other', ':field', (snip)]
     */
    const UNLESS = 'Unless:';

    /**
     * Satisfy Validation/Condition.
     * It checks the value will satisfy given test callback.
     * This validation does not output an error message by own.
     *
     * If you need to output an error message you have to call Context::appendError() in the $test callback.
     * That means you can do any validation test by this validation.
     *
     * Otherwise, you just write tests without Context::appendError(),
     * In that case this validation become a condition for 'then' and/or 'else'.
     *
     * ex)
     *   - ['CU', Valid::SATISFY, function(Context $c) { ...Any test with    appendError()... } : bool]
     *   - ['CU', Valid::SATISFY, function(Context $c) { ...Any test without appendError()... } : bool, 'then' => [...], 'else' => [...]]
     */
    const SATISFY = 'Satisfy:';

    /**
     * Required Validation.
     * It checks the value is not blank.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     *
     * ex)
     *   - ['CU', Valid::REQUIRED]
     * message)
     *   Key         - Required
     *   Placeholder - :attribute
     *   Selector    - none
     */
    const REQUIRED = 'Required:!';

    /**
     * Required If Validation.
     * It checks the value is not blank.
     * If 'other' field value is given 'value', then check the target fields.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     *
     * ex)
     *   - ['CU', Valid::REQUIRED_IF, 'other', value]
     *   - ['CU', Valid::REQUIRED_IF, 'other', [value1, value2, ...]]
     *   - ['CU', Valid::REQUIRED_IF, 'other', ':field']
     * message)
     *   Key         - RequiredIf
     *   Placeholder - :attribute, :selector, :other, :value
     *   Selector    - count of value
     */
    const REQUIRED_IF = 'RequiredIf:!';

    /**
     * Required Unless Validation.
     * It checks the value is not blank.
     * If 'other' field value is not given 'value', then check the target fields.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     * If you don't want to stop validation, you will give 'RequiredUnless' string.
     *
     * ex)
     *   - ['CU', Valid::REQUIRED_UNLESS, 'other', value]
     *   - ['CU', Valid::REQUIRED_UNLESS, 'other', [value1, value2, ...]]
     *   - ['CU', Valid::REQUIRED_UNLESS, 'other', ':field']
     * message)
     *   Key         - RequiredUnless
     *   Placeholder - :attribute, :selector, :other, :value
     *   Selector    - count of value
     */
    const REQUIRED_UNLESS = 'RequiredUnless:!';

    /**
     * Required With Validation.
     * It checks the value is not blank.
     * If 'other' fields are present at least N, then check the target fields.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     * If you don't want to stop validation, you will give 'RequiredWith' string.
     *
     * ex)
     *   - ['CU', Valid::REQUIRED_WITH, 'other'] (at_least: null)
     *   - ['CU', Valid::REQUIRED_WITH, ['other1', 'other2', ...]] (at_least: null)
     *   - ['CU', Valid::REQUIRED_WITH, ['other1', 'other2', ...], at_least]
     * message)
     *   Key         - RequiredWith
     *   Placeholder - :attribute, :selector, :other, :at_least
     *   Selector    - one(when other count is one), some(when at_least < other count), all
     */
    const REQUIRED_WITH = 'RequiredWith:!';

    /**
     * Required Without Validation.
     * It checks the value is not blank.
     * If 'other' fields are not present at least N, then check the target fields.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     * If you don't want to stop validation, you will give 'RequiredWithout' string.
     *
     * ex)
     *   - ['CU', Valid::REQUIRED_WITHOUT, 'other'] (at_least: null)
     *   - ['CU', Valid::REQUIRED_WITHOUT, ['other1', 'other2', ...]] (at_least: null)
     *   - ['CU', Valid::REQUIRED_WITHOUT, ['other1', 'other2', ...], at_least]
     * message)
     *   Key         - RequiredWithout
     *   Placeholder - :attribute, :selector, :other, :at_least
     *   Selector    - one(when other count is one), some(when at_least < other count), all
     */
    const REQUIRED_WITHOUT = 'RequiredWithout:!';

    /**
     * Blank If Validation.
     * It checks the value is blank.
     * If 'other' field value is given 'value', then check the target fields.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     *
     * ex)
     *   - ['CU', Valid::BLANK_IF, 'other', value]
     *   - ['CU', Valid::BLANK_IF, 'other', [value1, value2, ...]]
     *   - ['CU', Valid::BLANK_IF, 'other', ':field']
     * message)
     *   Key         - BlankIf
     *   Placeholder - :attribute, :self, :selector, :other, :value
     *   Selector    - count of value
     */
    const BLANK_IF = 'BlankIf:!';
    
    /**
     * Empty Unless Validation.
     * It checks the value is blank.
     * If 'other' field value is given 'value', then check the target fields.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     *
     * ex)
     *   - ['CU', Valid::BLANK_UNLESS, 'other', value]
     *   - ['CU', Valid::BLANK_UNLESS, 'other', [value1, value2, ...]]
     *   - ['CU', Valid::BLANK_UNLESS, 'other', ':field']
     * message)
     *   Key         - BlankUnless
     *   Placeholder - :attribute, :self, :selector, :other, :value
     *   Selector    - count of value
     */
    const BLANK_UNLESS = 'BlankUnless:!';
    
    /**
     * Blank With Validation.
     * It checks the value is blank.
     * If 'other' fields are present at least N, then check the target fields.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     *
     * ex)
     *   - ['CU', Valid::BLANK_WITH, 'other'] (at_least: null)
     *   - ['CU', Valid::BLANK_WITH, ['other1', 'other2', ...]] (at_least: null)
     *   - ['CU', Valid::BLANK_WITH, ['other1', 'other2', ...], at_least]
     * message)
     *   Key         - BlankWith
     *   Placeholder - :attribute, :self, :selector, :other, :at_least
     *   Selector    - one(when other count is one), some(when at_least < other count), all
     */
    const BLANK_WITH = 'BlankWith:!';

    /**
     * Blank Without Validation.
     * It checks the value is blank.
     * If 'other' fields are not present at least N, then check the target fields.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     *
     * ex)
     *   - ['CU', Valid::BLANK_WITHOUT, 'other'] (at_least: null)
     *   - ['CU', Valid::BLANK_WITHOUT, ['other1', 'other2', ...]] (at_least: null)
     *   - ['CU', Valid::BLANK_WITHOUT, ['other1', 'other2', ...], at_least]
     * message)
     *   Key         - BlankWith
     *   Placeholder - :attribute, :self, :selector, :other, :at_least
     *   Selector    - one(when other count is one), some(when at_least < other count), all
     */
    const BLANK_WITHOUT = 'BlankWithout:!';

    /**
     * Same As Validation.
     * It checks the value will same as a given value or field.
     *
     * ex)
     *   - ['CU', Valid::SAME_AS, value]
     *   - ['CU', Valid::SAME_AS, ':field']
     * message)
     *   Key         - SameAs
     *   Placeholder - :attribute, :self, :value
     *   Selector    - none
     */
    const SAME_AS = 'SameAs:';

    /**
     * Not Same As Validation.
     * It checks the value will not same as a given value or field.
     *
     * ex)
     *   - ['CU', Valid::NOT_SAME_AS, value]
     *   - ['CU', Valid::NOT_SAME_AS, ':field']
     * message)
     *   Key         - NotSameAs
     *   Placeholder - :attribute, :self, :value
     *   Selector    - none
     */
    const NOT_SAME_AS = 'NotSameAs:';

    /**
     * Regex Validation.
     * It checks the value will match given pattern.
     *
     * ex)
     *   - ['CU', Valid::REGEX, pattern] (selector: null)
     *   - ['CU', Valid::REGEX, pattern, selector]
     * message)
     *   Key         - Regex, Regex@List
     *   Placeholder - :attribute, :self, :selector, :pattern, :nth, :value
     *   Selector    - none or given selector
     */
    const REGEX = 'Regex:';
    
    /**
     * Not Regex Validation.
     * It checks the value will not match given pattern.
     *
     * ex)
     *   - ['CU', Valid::NOT_REGEX, pattern] (selector: null)
     *   - ['CU', Valid::NOT_REGEX, pattern, selector]
     * message)
     *   Key         - NotRegex, NotRegex@List
     *   Placeholder - :attribute, :self, :selector, :pattern, :nth, :value
     *   Selector    - none or given selector
     */
    const NOT_REGEX = 'NotRegex:';

    /**
     * Max Length Validation.
     * It checks the value character length will less equal given max length.
     *
     * ex)
     *   - ['CU', Valid::MAX_LENGTH, max]
     * message)
     *   Key         - MaxLength, MaxLength@List
     *   Placeholder - :attribute, :self, :max, :nth, :value
     *   Selector    - none
     */
    const MAX_LENGTH = 'MaxLength:';

    /**
     * Min Length Validation.
     * It checks the value character length will greater equal given min length.
     *
     * ex)
     *   - ['CU', Valid::MIN_LENGTH, min]
     * message)
     *   Key         - MinLength, MinLength@List
     *   Placeholder - :attribute, :self, :min, :nth, :value
     *   Selector    - none
     */
    const MIN_LENGTH = 'MinLength:';

    /**
     * Length Validation.
     * It checks the value character length will equal given length.
     *
     * ex)
     *   - ['CU', Valid::LENGTH, length]
     * message)
     *   Key         - Length, Length@List
     *   Placeholder - :attribute, :self, :length, :nth, :value
     *   Selector    - none
     */
    const LENGTH = 'Length:';

    /**
     * Number Validation.
     * It checks the value is number.
     *
     * ex)
     *   - ['CU', Valid::NUMBER]
     * message)
     *   Key         - Number, Number@List
     *   Placeholder - :attribute, :self, :nth, :value
     *   Selector    - none
     */
    const NUMBER = 'Number:';

    /**
     * Integer Validation.
     * It checks the value is integer.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     *
     * ex)
     *   - ['CU', Valid::INTEGER]
     * message)
     *   Key         - Integer, Integer@List
     *   Placeholder - :attribute, :self, :nth, :value
     *   Selector    - none
     */
    const INTEGER = 'Integer:!';

    /**
     * Float Validation.
     * It checks the value is real number up to given number decimal places.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     *
     * ex)
     *   - ['CU', Valid::FLOAT, decimal]
     * message)
     *   Key         - Float, Float@List
     *   Placeholder - :attribute, :self, :decimal, :nth, :value
     *   Selector    - none
     */
    const FLOAT = 'Float:!';
    
    /**
     * Number Less Than Validation.
     * It checks the value number will less than given number.
     * This validation use Valid::INTEGER or Valid::FLOAT validation for type consistency check.
     * If a given decimal is 0 then use Valid::INTEGER validation, otherwise use Valid::FLOAT validation.
     *
     * ex)
     *   - ['CU', Valid::NUMBER_LESS_THAN, number] (decimal: 0)
     *   - ['CU', Valid::NUMBER_LESS_THAN, number, decimal]
     *   - ['CU', Valid::NUMBER_LESS_THAN, ':field', (snip)]
     * message)
     *   Key         - NumberLessThan, NumberLessThan@List
     *   Placeholder - :attribute, :self, :number, :decimal, :nth, :value
     *   Selector    - none
     */
    const NUMBER_LESS_THAN = 'NumberLessThan:';

    /**
     * Number Less Than Or Equal Validation.
     * It checks the value number will less than or equal given number.
     * This validation use Valid::INTEGER or Valid::FLOAT validation for type consistency check.
     * If a given decimal is 0 then use Valid::INTEGER validation, otherwise use Valid::FLOAT validation.
     *
     * ex)
     *   - ['CU', Valid::NUMBER_LESS_THAN_OR_EQUAL, number] (decimal: 0)
     *   - ['CU', Valid::NUMBER_LESS_THAN_OR_EQUAL, number, decimal]
     *   - ['CU', Valid::NUMBER_LESS_THAN_OR_EQUAL, ':field', (snip)]
     * message)
     *   Key         - NumberLessThanOrEqual, NumberLessThanOrEqual@List
     *   Placeholder - :attribute, :self, :number, :decimal, :nth, :value
     *   Selector    - none
     */
    const NUMBER_LESS_THAN_OR_EQUAL = 'NumberLessThanOrEqual:';
    
    /**
     * Number Greater Than Validation.
     * It checks the value number will greater than or equal given min number.
     * This validation use Valid::INTEGER or Valid::FLOAT validation for type consistency check.
     * If a given decimal is 0 then use Valid::INTEGER validation, otherwise use Valid::FLOAT validation.
     *
     * ex)
     *   - ['CU', Valid::NUMBER_GREATER_THAN, number] (decimal: 0)
     *   - ['CU', Valid::NUMBER_GREATER_THAN, number, decimal]
     *   - ['CU', Valid::NUMBER_GREATER_THAN, ':field', (snip)]
     * message)
     *   Key         - NumberGreaterThan, NumberGreaterThan@List
     *   Placeholder - :attribute, :self, :number, :decimal, :nth, :value
     *   Selector    - none
     */
    const NUMBER_GREATER_THAN = 'NumberGreaterThan:';

    /**
     * Number Greater Than Or Equal Validation.
     * It checks the value number will greater than or equal given min number.
     * This validation use Valid::INTEGER or Valid::FLOAT validation for type consistency check.
     * If a given decimal is 0 then use Valid::INTEGER validation, otherwise use Valid::FLOAT validation.
     *
     * ex)
     *   - ['CU', Valid::NUMBER_GREATER_THAN_OR_EQUAL, number] (decimal: 0)
     *   - ['CU', Valid::NUMBER_GREATER_THAN_OR_EQUAL, number, decimal]
     *   - ['CU', Valid::NUMBER_GREATER_THAN_OR_EQUAL, ':field', (snip)]
     * message)
     *   Key         - NumberGreaterThanOrEqual, NumberGreaterThanOrEqual@List
     *   Placeholder - :attribute, :self, :number, :decimal, :nth, :value
     *   Selector    - none
     */
    const NUMBER_GREATER_THAN_OR_EQUAL = 'NumberGreaterThanOrEqual:';
    
    /**
     * Email Validation.
     * It checks the value format is mail address.
     * If the given strict is ture then use filter_var($value, FILTER_VALIDATE_EMAIL),
     * otherwise use loose regular expression.
     * This non strict mode validation also allows mail address format not compliant with RFC
     * which could be created by Japanese carriers in the past.
     *
     * ex)
     *   - ['CU', Valid::EMAIL] (strict: true)
     *   - ['CU', Valid::EMAIL, strict]
     * message)
     *   Key         - Email, Email@List
     *   Placeholder - :attribute, :self, :nth, :value
     *   Selector    - none
     */
    const EMAIL = 'Email:';

    /**
     * Url Validation.
     * It checks the value format is url.
     * If the given dns_check is ture then use dns_get_record() to check dns is active.
     *
     * ex)
     *   - ['CU', Valid::URL] (dns_check: false)
     *   - ['CU', Valid::URL, dns_check]
     * message)
     *   Key         - Url, Url@List
     *   Placeholder - :attribute, :self, :nth, :value
     *   Selector    - none or nonactive(when dns_check and dns is not active)
     */
    const URL = 'Url:';

    /**
     * IPv4 Validation.
     * It checks the value format is IPv4 with/without CIDR.
     * If the delimiter will be given then split value by the given delimiter first and trim each then validate.
     *
     * ex)
     *   - ['CU', Valid::IPV4] (delimiter: null)
     *   - ['CU', Valid::IPV4, delimiter]
     * message)
     *   Key         - Ipv4, Ipv4@List
     *   Placeholder - :attribute, :self, :delimiter, :nth, :value
     *   Selector    - none
     */
    const IPV4 = 'Ipv4:';

    /**
     * Digit Validation.
     * It checks the value may only contain half digits.
     *
     * ex)
     *   - ['CU', Valid::DIGIT]
     * message)
     *   Key         - Digit, Digit@List
     *   Placeholder - :attribute, :self, :nth, :value
     *   Selector    - none
     */
    const DIGIT = 'Digit:';

    /**
     * Alpha Validation.
     * It checks the value may only contain half alphabets.
     *
     * ex)
     *   - ['CU', Valid::ALPHA]
     * message)
     *   Key         - Alpha, Alpha@List
     *   Placeholder - :attribute, :self, :nth, :value
     *   Selector    - none
     */
    const ALPHA = 'Alpha:';

    /**
     * Alpha Digit Validation.
     * It checks the value may only contain half alphabets or digits.
     *
     * ex)
     *   - ['CU', Valid::ALPHA_DIGIT]
     * message)
     *   Key         - AlphaDigit, AlphaDigit@List
     *   Placeholder - :attribute, :self, :nth, :value
     *   Selector    - none
     */
    const ALPHA_DIGIT = 'AlphaDigit:';

    /**
     * Alpha Digit Mark Validation.
     * It checks the value may only contain half alphabets, digits or given marks.
     *
     * ex)
     *   - ['CU', Valid::ALPHA_DIGIT_MARK] (mark: '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~ ')
     *   - ['CU', Valid::ALPHA_DIGIT_MARK, mark]
     * message)
     *   Key         - AlphaDigitMark, AlphaDigitMark@List
     *   Placeholder - :attribute, :self, :mark, :nth, :value
     *   Selector    - none
     */
    const ALPHA_DIGIT_MARK = 'AlphaDigitMark:';

    /**
     * Hiragana Validation.
     * It checks the value may only contain Hiragana in Japanese.
     * This validation not allowed full width space, so you can use extra if you want to allow full width space and etc.
     *
     * ex)
     *   - ['CU', Valid::HIRAGANA] (extra: '')
     *   - ['CU', Valid::HIRAGANA, extra]
     * message)
     *   Key         - Hiragana, Hiragana@List
     *   Placeholder - :attribute, :self, :extra, :nth, :value
     *   Selector    - none
     */
    const HIRAGANA = 'Hiragana:';

    /**
     * Kana Validation.
     * It checks the value may only contain full width Kana in Japanese.
     * This validation not allowed full width space, so you can use extra if you want to allow full width space and etc.
     *
     * ex)
     *   - ['CU', Valid::KANA] (extra: '')
     *   - ['CU', Valid::KANA, extra]
     * message)
     *   Key         - Kana, Kana@List
     *   Placeholder - :attribute, :self, :extra, :nth, :value
     *   Selector    - none
     */
    const KANA = 'Kana:';

    /**
     * Dependence Char Validation.
     * It checks the value contain platform dependent characters.
     *
     * ex)
     *   - ['CU', Valid::DEPENDENCE_CHAR] (encode: depend on configure)
     *   - ['CU', Valid::DEPENDENCE_CHAR, encode]
     * message)
     *   Key         - DependenceChar, DependenceChar@List
     *   Placeholder - :attribute, :self, :encode, :dependences, :nth, :value
     *   Selector    - none
     */
    const DEPENDENCE_CHAR = 'DependenceChar:';

    /**
     * Ng Word Validation.
     * It checks the value contains the given ng words.
     *
     * $ng_words is the file path of the array or word list.
     * Word list is defined by a line break separator.
     * Registering word like below will result in an ambiguous search.
     *
     * · Alphanumeric characters are lowercase letters
     * · Japanese is full-width Kana and Kanji
     * · In other languages, you can add definitions in config settings
     *
     * You can also make a absolute match search by defining short words as ^○○$.
     *
     * ex)
     *   - ['CU', Valid::NG_WORD, 'ng_words_file_path']
     *   - ['CU', Valid::NG_WORD, ['ng_word1', 'ng_word2', ...]]
     *   - ['CU', Valid::NG_WORD, ng_words, word_split_pattern, delimiter_pattern, omission_pattern, omission_length, omission_ratio]
     *       $word_split_pattern: (default: depend on configure)
     *         The character specified here is using as word split letters when checking.
     *       $delimiter_pattern: (default: depend on configure)
     *         The character specified here is ignored as a delimiter when checking.
     *         ex) It will specify delimiter like dot and space to match 'd.u.m.m.y' or 'd u m m y' to 'dummy'.
     *       $omission_pattern: (default: depend on configure)
     *         The character specified here will be processed as an omission character when checking.
     *         ex) It will specify omission character like circle and asterisk to match 'd○mmy' or 'dum*y' to 'dummy'.
     *       $omission_length: (default: depend on configure)
     *         The minimum ng word length to apply omission character pattern check.
     *       $omission_Ratio: (default: depend on configure)
     *         The ratio of omission characters in ng words.
     *         ex) In the case of 0.4 setting, 'a*s', 'dum*y', 'd*m*y' match 'ass' and 'dummy' respectively, but '*s*', 'd***y' does not match.
     * message)
     *   Key         - NgWord, NgWord@List
     *   Placeholder - :attribute, :self, :nth, :value
     *   Selector    - none
     */
    const NG_WORD = 'NgWord:';

    /**
     * Contains Validation.
     * It checks the value contain given list.
     *
     * ex)
     *   - ['CU', Valid::CONTAINS, [value1, value2, ...]]
     *   - ['CU', Valid::CONTAINS, Enum::values()]
     * message)
     *   Key         - ContainsChar, ContainsChar@List
     *   Placeholder - :attribute, :self, :list, :nth, :value
     *   Selector    - none
     */
    const CONTAINS = 'Contains:';

    /**
     * Min Count Validation.
     * It checks the value must have at least min items.
     *
     * ex)
     *   - ['CU', Valid::MIN_COUNT, min]
     * message)
     *   Key         - MinCount
     *   Placeholder - :attribute, :self, :item_count, :min
     *   Selector    - number of given min
     */
    const MIN_COUNT = 'MinCount:';

    /**
     * Max Count Validation.
     * It checks the value may not have more than max items.
     *
     * ex)
     *   - ['CU', Valid::MAX_COUNT, max]
     * message)
     *   Key         - MaxCount
     *   Placeholder - :attribute, :self, :item_count, :max
     *   Selector    - number of given max
     */
    const MAX_COUNT = 'MaxCount:';

    /**
     * Count Validation.
     * It checks the value have count items.
     *
     * ex)
     *   - ['CU', Valid::COUNT, count]
     * message)
     *   Key         - Count
     *   Placeholder - :attribute, :self, :item_count, :count
     *   Selector    - number of given count
     */
    const COUNT = 'Count:';

    /**
     * Unique Validation.
     * It checks the value have unique items.
     *
     * ex)
     *   - ['CU', Valid::UNIQUE] (nested_field: null)
     *   - ['CU', Valid::UNIQUE, ':nested_field']
     * message)
     *   Key         - Unique
     *   Placeholder - :attribute, :self, :duplicate
     *   Selector    - count of duplicate
     */
    const UNIQUE = 'Unique:';

    /**
     * Datetime Validation.
     * It checks the value format is datetime.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     *
     * ex)
     *   - ['CU', Valid::DATETIME] (format: [])
     *   - ['CU', Valid::DATETIME, format]
     *   - ['CU', Valid::DATETIME, [format1, format2, ...]]
     * message)
     *   Key         - Datetime, Datetime@List
     *   Placeholder - :attribute, :self, :nth, :value
     *   Selector    - none
     */
    const DATETIME = 'Datetime:!';

    /**
     * Future Than Validation.
     * It checks the value of datetime is future than at_time.
     *
     * Argument of at_time support strtotime() format.
     * If given at_time can not analyze by given format and acceptable_format then it will try to analyze by strtotime() format.
     *
     * ex)
     *   - ['CU', Valid::FUTURE_THAN, 'at_time'] (format: [])
     *   - ['CU', Valid::FUTURE_THAN, 'at_time', format]
     *   - ['CU', Valid::FUTURE_THAN, 'at_time', [format1, format2, ...]]
     *   - ['CU', Valid::FUTURE_THAN, ':field' , (snip)]
     * message)
     *   Key         - FutureThan, FutureThan@List
     *   Placeholder - :attribute, :self, :at_time, :nth, :value
     *   Selector    - none
     */
    const FUTURE_THAN = 'FutureThan:';

    /**
     * Future Equal Or Equal Validation.
     * It checks the value of datetime is future than or equal at_time.
     *
     * Argument of at_time support strtotime() format.
     * If given at_time can not analyze by given format and acceptable_format then it will try to analyze by strtotime() format.
     *
     * ex)
     *   - ['CU', Valid::FUTURE_THAN_OR_EQUAL, 'at_time'] (format: [])
     *   - ['CU', Valid::FUTURE_THAN_OR_EQUAL, 'at_time', format]
     *   - ['CU', Valid::FUTURE_THAN_OR_EQUAL, 'at_time', [format1, format2, ...]]
     *   - ['CU', Valid::FUTURE_THAN_OR_EQUAL, ':field' , (snip)]
     * message)
     *   Key         - FutureThanOrEqual, FutureThanOrEqual@List
     *   Placeholder - :attribute, :self, :at_time, :nth, :value
     *   Selector    - none
     */
    const FUTURE_THAN_OR_EQUAL = 'FutureThanOrEqual:';

    /**
     * Past Than Validation.
     * It checks the value of datetime is future than at_time.
     *
     * Argument of at_time support strtotime() format.
     * If given at_time can not analyze by given format and acceptable_format then it will try to analyze by strtotime() format.
     *
     * ex)
     *   - ['CU', Valid::PAST_THAN, 'at_time'] (format: [])
     *   - ['CU', Valid::PAST_THAN, 'at_time', format]
     *   - ['CU', Valid::PAST_THAN, 'at_time', [format1, format2, ...]]
     *   - ['CU', Valid::PAST_THAN, ':field' , (snip)]
     * message)
     *   Key         - PastThan, PastThan@List
     *   Placeholder - :attribute, :self, :at_time, :nth, :value
     *   Selector    - none
     */
    const PAST_THAN = 'PastThan:';

    /**
     * Past Equal Or Equal Validation.
     * It checks the value of datetime is future than or equal at_time.
     *
     * Argument of at_time support strtotime() format.
     * If given at_time can not analyze by given format and acceptable_format then it will try to analyze by strtotime() format.
     *
     * ex)
     *   - ['CU', Valid::PAST_THAN_OR_EQUAL, 'at_time'] (format: [])
     *   - ['CU', Valid::PAST_THAN_OR_EQUAL, 'at_time', format]
     *   - ['CU', Valid::PAST_THAN_OR_EQUAL, 'at_time', [format1, format2, ...]]
     *   - ['CU', Valid::PAST_THAN_OR_EQUAL, ':field' , (snip)]
     * message)
     *   Key         - PastThanOrEqual, PastThanOrEqual@List
     *   Placeholder - :attribute, :self, :at_time, :nth, :value
     *   Selector    - none
     */
    const PAST_THAN_OR_EQUAL = 'PastThanOrEqual:';

    /**
     * Max Age Validation.
     * It checks the value of datetime less than or equal given max age when at_time.
     *
     * Argument of at_time support strtotime() format.
     * If given at_time can not analyze by given format and acceptable_format then it will try to analyze by strtotime() format.
     *
     * ex)
     *   - ['CU', Valid::MAX_AGE, max] (at_time: 'today', format: [])
     *   - ['CU', Valid::MAX_AGE, max, 'at_time'] (format: [])
     *   - ['CU', Valid::MAX_AGE, max, 'at_time', format]
     *   - ['CU', Valid::MAX_AGE, max, 'at_time', [format1, format2, ...]]
     *   - ['CU', Valid::MAX_AGE, ':field', ':field' , (snip)]
     * message)
     *   Key         - MaxAge, MaxAge@List
     *   Placeholder - :attribute, :self, :max, :at_time, :nth, :value
     *   Selector    - none or 'at_time'(when at_time is not 'today')
     */
    const MAX_AGE = 'MaxAge:';

    /**
     * Min Age Validation.
     * It checks the value of datetime greater than or equal given min age when at_time.
     *
     * Argument of at_time support strtotime() format.
     * If given at_time can not analyze by given format and acceptable_format then it will try to analyze by strtotime() format.
     *
     * ex)
     *   - ['CU', Valid::MIN_AGE, min] (at_time: 'today', format: [])
     *   - ['CU', Valid::MIN_AGE, min, 'at_time'] (format: [])
     *   - ['CU', Valid::MIN_AGE, min, 'at_time', format]
     *   - ['CU', Valid::MIN_AGE, min, 'at_time', [format1, format2, ...]]
     *   - ['CU', Valid::MIN_AGE, ':field', ':field' , (snip)]
     * message)
     *   Key         - MinAge, MinAge@List
     *   Placeholder - :attribute, :self, :min, :at_time, :nth, :value
     *   Selector    - none or 'at_time'(when at_time is not 'today')
     */
    const MIN_AGE = 'MinAge:';


    const AGE_GREATER_EQUAL = 'AgeGreaterEqual:';
}
