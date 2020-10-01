<?php
namespace Rebet\Validation;

use Rebet\Tools\Enum\Enum;

/**
 * Validation Check Kind Enum Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Kind extends Enum
{
    /**
     * This is a type that represents type consistency check.
     *
     * A type consistency check is specified if subsequent validation can not be performed when the validation fails.
     * ex) Date format validation before date and time comparison
     * ex) Numeric format validation before numeric comparison
     */
    const TYPE_CONSISTENCY_CHECK = [1, 'TYPE_CONSISTENCY_CHECK'];

    /**
     * This is a type that represents type dependent check.
     *
     * A type dependent check is specified if the validation can not be performed when pre validation fails.
     * ex) Date and time comparison after date format validation
     * ex) Numeric comparison after numeric format validation
     */
    const TYPE_DEPENDENT_CHECK = [2, 'TYPE_DEPENDENT_CHECK'];

    /**
     * This is a type that represents independently check.
     *
     * An independently check is specified if the validation can be performed independently.
     */
    const INDEPENDENTLY = [3, 'INDEPENDENTLY'];

    /**
     * This enum do not need to translate.
     *
     * @return boolean
     */
    protected function translatable() : bool
    {
        return false;
    }
}
