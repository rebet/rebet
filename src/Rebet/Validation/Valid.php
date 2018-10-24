<?php
namespace Rebet\Validation;

/**
 * Valid Class
 *
 * This class is constants list of built-in validation names.
 * Some definitions (ex REQUIRED/DATE_TIME) includes '!' (validation stop if failed) option defaultly.
 *
 * The validation names of Rebet is a naming rule assuming a forward match type IDE input completion.
 * For example,
 *
 *   Usually    => In Rebet
 *   ------------------------
 *   LENGTH     => LENGTH
 *   MIN_LENGTH => LENGTH_MIN
 *   MAX_LENGTH => LENGTH_MAX
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
     * This validation does not output an error message even test is failed.
     * This behavior is useful for describing validation execution conditions using 'then' or/and 'else'.
     *
     * ex)
     *   - ['CU', Valid::IF, 'other', value, 'then' => [...], 'else' => [...]]
     *   - ['CU', Valid::IF, 'other', [value1, value2, ...], 'then' => [...], 'else' => [...]]
     *   - ['CU', Valid::IF, 'other', ':field', 'then' => [...], 'else' => [...]]
     */
    const IF = 'If:';

    /**
     * Unless Condition.
     * This validation does not output an error message even test is failed.
     * This behavior is useful for describing validation execution conditions using 'then' or/and 'else'.
     *
     * ex)
     *   - ['CU', Valid::UNLESS, 'other', value, 'then' => [...], 'else' => [...]]
     *   - ['CU', Valid::UNLESS, 'other', [value1, value2, ...], 'then' => [...], 'else' => [...]]
     *   - ['CU', Valid::UNLESS, 'other', ':field', 'then' => [...], 'else' => [...]]
     */
    const UNLESS = 'Unless:';

    /**
     * Satisfy Validation/Condition.
     * This validation does not output an error message by own.
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
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     * If you don't want to stop validation, you will give 'Required' string.
     *
     * ex)
     *   - ['CU', Valid::REQUIRED]
     */
    const REQUIRED = 'Required:!';

    /**
     * Required If Validation.
     * If 'other' field value is given 'value', then check the target fields.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     * If you don't want to stop validation, you will give 'RequiredIf' string.
     *
     * ex)
     *   - ['CU', Valid::REQUIRED_IF, 'other', value]
     *   - ['CU', Valid::REQUIRED_IF, 'other', [value1, value2, ...]]
     *   - ['CU', Valid::REQUIRED_IF, 'other', ':field']
     */
    const REQUIRED_IF = 'RequiredIf:!';

    /**
     * Required Unless Validation.
     * If 'other' field value is not given 'value', then check the target fields.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     * If you don't want to stop validation, you will give 'RequiredUnless' string.
     *
     * ex)
     *   - ['CU', Valid::REQUIRED_UNLESS, 'other', value]
     *   - ['CU', Valid::REQUIRED_UNLESS, 'other', [value1, value2, ...]]
     *   - ['CU', Valid::REQUIRED_UNLESS, 'other', ':field']
     */
    const REQUIRED_UNLESS = 'RequiredUnless:!';

    /**
     * Required With Validation.
     * If 'other' fields are present at least N, then check the target fields.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     * If you don't want to stop validation, you will give 'RequiredWith' string.
     *
     * ex)
     *   - ['CU', Valid::REQUIRED_WITH, 'other']
     *   - ['CU', Valid::REQUIRED_WITH, ['other1', 'other2', ...]]
     *   - ['CU', Valid::REQUIRED_WITH, ['other1', 'other2', ...], 1]
     */
    const REQUIRED_WITH = 'RequiredWith:!';

    /**
     * Required Without Validation.
     * If 'other' fields are not present at least N, then check the target fields.
     * This validation constant includes '!'(validation stop if failed) option defaultly.
     * If you don't want to stop validation, you will give 'RequiredWithout' string.
     *
     * ex)
     *   - ['CU', Valid::REQUIRED_WITHOUT, 'other']
     *   - ['CU', Valid::REQUIRED_WITHOUT, ['other1', 'other2', ...]]
     *   - ['CU', Valid::REQUIRED_WITHOUT, ['other1', 'other2', ...], 1]
     */
    const REQUIRED_WITHOUT = 'RequiredWithout:!';

    /**
     * Blank If Validation.
     * If 'other' field value is given 'value', then check the target fields.
     *
     * ex)
     *   - ['CU', Valid::BLANK_IF, 'other', value]
     *   - ['CU', Valid::BLANK_IF, 'other', [value1, value2, ...]]
     *   - ['CU', Valid::BLANK_IF, 'other', ':field']
     */
    const BLANK_IF = 'BlankIf:';
    
    /**
     * Empty Unless Validation.
     * If 'other' field value is given 'value', then check the target fields.
     *
     * ex)
     *   - ['CU', Valid::BLANK_UNLESS, 'other', value]
     *   - ['CU', Valid::BLANK_UNLESS, 'other', [value1, value2, ...]]
     *   - ['CU', Valid::BLANK_UNLESS, 'other', ':field']
     */
    const BLANK_UNLESS = 'BlankUnless:';
    
    /**
     * Blank With Validation.
     * If 'other' fields are present at least N, then check the target fields.
     *
     * ex)
     *   - ['CU', Valid::BLANK_WITH, 'other']
     *   - ['CU', Valid::BLANK_WITH, ['other1', 'other2', ...]]
     *   - ['CU', Valid::BLANK_WITH, ['other1', 'other2', ...], 1]
     */
    const BLANK_WITH = 'BlankWith:';

    /**
     * Blank Without Validation.
     * If 'other' fields are not present at least N, then check the target fields.
     *
     * ex)
     *   - ['CU', Valid::BLANK_WITHOUT, 'other']
     *   - ['CU', Valid::BLANK_WITHOUT, ['other1', 'other2', ...]]
     *   - ['CU', Valid::BLANK_WITHOUT, ['other1', 'other2', ...], 1]
     */
    const BLANK_WITHOUT = 'BlankWithout:';




    
    const LENGTH_MAX = 'LengthMax:';


    const DEPENDENCE_CHAR = 'DependenceChar:';


    const DATETIME = 'Datetime:';


    const AGE_GREATER_EQUAL = 'AgeGreaterEqual:';
}
