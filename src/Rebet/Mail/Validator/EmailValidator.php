<?php
namespace Rebet\Mail\Validator;

use Egulias\EmailValidator\EmailValidator as EguliasEmailValidator;
use Egulias\EmailValidator\Validation\EmailValidation;
use Rebet\Mail\Mail;

/**
 * Email Validator Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class EmailValidator extends EguliasEmailValidator
{
    /**
     * {@inheritDoc}
     *
     * This method not using given $email_validation, Instead use 'email.validation' from Swift DI Container.
     */
    public function isValid($email, EmailValidation $email_validation)
    {
        return parent::isValid($email, Mail::container()->lookup('email.validation'));
    }
}
