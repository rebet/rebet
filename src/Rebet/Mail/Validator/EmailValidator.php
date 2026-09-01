<?php
namespace Rebet\Mail\Validator;

use Egulias\EmailValidator\EmailValidator as EguliasEmailValidator;
use Egulias\EmailValidator\Validation\EmailValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use Override;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Reflection\Reflector;
use Symfony\Component\Mime\Address;

/**
 * Email Validator Class
 *
 * An extension of egulias/email-validator's EmailValidator that always validates using the
 * {@see EmailValidation} strategy configured at 'EmailValidator.validation' (see
 * {@see defaultConfig()}, {@see setValidation()}), instead of whatever strategy is passed to
 * {@see isValid()}. {@see enable()} installs this configurable validator as the one used
 * internally by Symfony's {@see Address} (e.g. for From/To/Cc addresses), so the configured
 * validation strategy applies application-wide; {@see reset()} restores the validator that
 * was in use before {@see enable()} was first called.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class EmailValidator extends EguliasEmailValidator
{
    use Configurable;

    /**
     * The validator that Symfony's {@see Address} was using before {@see enable()} was first
     * called, so {@see reset()} can restore it. Null means {@see enable()} has not been
     * called (yet), so there is nothing to restore.
     *
     * @var EguliasEmailValidator|null
     */
    protected static EguliasEmailValidator|null $original_validator = null;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public static function defaultConfig()
    {
        return [
            'validation' => new RFCValidation(),
        ];
    }

    /**
     * Set the validation strategy to be used by {@see isValid()}.
     *
     * @param EmailValidation $validation
     * @return void
     */
    public static function setValidation(EmailValidation $validation) : void
    {
        static::setConfig(['validation' => $validation]);
    }

    /**
     * Install a new instance of this configurable validator as the (private, static) validator
     * used internally by Symfony's {@see Address}, so every {@see Address} it constructs is
     * validated using the strategy configured at 'EmailValidator.validation'.
     *
     * The validator {@see Address} was using before this first call is saved to
     * {@see $original_validator}, so a later {@see reset()} call can restore it. Constructing
     * a throwaway {@see Address} first forces its (lazily-initialized) validator property to be
     * initialized, since it can not otherwise be read.
     *
     * @return void
     */
    public static function enable() : void
    {
        $address           = new Address('for-init-static-field@rebet.local');
        $current_validator = Reflector::get($address, 'validator', null, true);
        if ($current_validator instanceof EmailValidator) {
            // Already enabled, nothing to do.
            return;
        }
        if (static::$original_validator === null) {
            static::$original_validator = $current_validator;
        }
        Reflector::set($address, 'validator', new EmailValidator());
    }

    /**
     * Restore the validator that Symfony's {@see Address} was using before {@see enable()} was
     * first called. Does nothing if {@see enable()} has not been called.
     *
     * @return void
     */
    public static function reset() : void
    {
        if (static::$original_validator !== null) {
            $class = Address::class;
            Reflector::set($class, 'validator', static::$original_validator);
        }
    }

    /**
     * {@inheritDoc}
     *
     * This method ignores the given $validation and always validates using the strategy
     * configured at 'EmailValidator.validation' instead (see {@see setValidation()}).
     *
     * @param string $email
     * @param EmailValidation $validation ignored
     * @return bool
     */
    #[Override]
    public function isValid($email, EmailValidation $validation)
    {
        return parent::isValid($email, static::configInstantiate('validation'));
    }
}
