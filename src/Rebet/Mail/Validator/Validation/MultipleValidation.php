<?php
namespace Rebet\Mail\Validator\Validation;

use Egulias\EmailValidator\Validation\EmailValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;

/**
 * Multiple Validation Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class MultipleValidation extends MultipleValidationWithAnd
{
    /**
     * It will be able to create Multiple Validation using Swift_DependencyContainer::withDependencies()
     *
     * @param EmailValidation ...$validations
     */
    public function __construct(EmailValidation ...$validations)
    {
        parent::__construct($validations);
    }
}
