<?php

declare(strict_types=1);

namespace Rebet\Validation;

use Rebet\Tools\Enum\Enumerable;
use Rebet\Tools\Enum\Enumeration;

/**
 * Validation Check Kind Enum
 *
 * The label of this enum is not display text, so that it does not have the Translatable attribute (it is not translated).
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @implements Enumeration<Kind, int>
 */
enum Kind: int implements Enumeration
{
    /** @use Enumerable<Kind, int> */
    use Enumerable;

    /**
     * This is a type that represents type consistency check.
     *
     * A type consistency check is specified if subsequent validation can not be performed when the validation fails.
     * ex) Date format validation before date and time comparison
     * ex) Numeric format validation before numeric comparison
     */
    case TypeConsistencyCheck = 1;

    /**
     * This is a type that represents type dependent check.
     *
     * A type dependent check is specified if the validation can not be performed when pre validation fails.
     * ex) Date and time comparison after date format validation
     * ex) Numeric comparison after numeric format validation
     */
    case TypeDependentCheck = 2;

    /**
     * This is a type that represents independently check.
     *
     * An independently check is specified if the validation can be performed independently.
     */
    case Independently = 3;
}
