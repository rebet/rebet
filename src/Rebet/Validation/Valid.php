<?php
namespace Rebet\Validation;

/**
 * Valid Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Valid
{
    /**
     * If condition.
     * This validation does not output an error message even test is failed.
     * This behavior is useful for describing validation execution conditions using 'then' or/and 'else'.
     *
     * ex)
     *   - ['CU', Valid::IF, function(Context $c) { ...Any test that returns boolean... }, 'then' => [...(part of rule)...], 'else' => [...(part of rule)...]]
     */
    const IF = 'If:';


    /**
     * Required validation.
     * This validation const includes '!'(validation stop if failed) option defaultly.
     * If you don't want to stop validation, you will give 'Required' string.
     *
     * ex)
     *   - ['CU', Valid::REQUIRED]
     *   - ['CU', 'Required']
     */
    const REQUIRED = 'Required:!';

    /**
     * RequiredIf validation.
     * If 'other' field value is given 'value', then check the target fields.
     * This validation const includes '!'(validation stop if failed) option defaultly.
     * If you don't want to stop validation, you will give 'RequiredIf' string.
     *
     * ex)
     *   - ['CU', Valid::REQUIRED_IF, 'other', value]
     */
    const REQUIRED_IF = 'RequiredIf:!';

    
    
    
    const MAX_LENGTH = 'MaxLength:';


    const DEPENDENCE_CHAR = 'DependenceChar:';


    const DATETIME = 'Datetime:';


    const AGE_GREATER_EQUAL = 'AgeGreaterEqual:';
}
